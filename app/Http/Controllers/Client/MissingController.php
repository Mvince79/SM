<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Http\Requests\Missing\SearchMissingRequest;
use App\Http\Requests\Missing\StoreMissingChildRequest;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\MissingChildService;
use App\Models\HandoverPoint;

class MissingController extends Controller
{
    public function __construct(
        private MissingChildService $missingChildService
    ) {
    }

    public function index(SearchMissingRequest $request)
    {
        $data = $this->missingChildService->getListForPublic($request->filters());

        return view('missing.index', $data);

    }

    public function detail(int $id)
    {
        $data = $this->missingChildService->getDetailForPublic($id);

        // Lấy danh sách HandoverPoint dựa theo province_id của trẻ
        $provinceId = $data['case']->province_id ?? $data['case']->last_seen_province_id ?? null;
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($provinceId);

        return view('missing.detail', $data);
    }

    public function qr(int $id)
    {
        $case = MissingChild::query()->findOrFail($id);
        $detailUrl = route('missing.detail', ['id' => $case->id]);

        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($detailUrl);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function detailByCaseCode(string $caseCode)
    {
        $data = $this->missingChildService->getDetailForPublicByCaseCode($caseCode);

        return view('missing.detail', $data);
    }
    public function detailBySlug(string $slug)
    {
        $missingChild = MissingChild::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->missingChildService->getDetailForPublic($missingChild->id);

        // Lấy danh sách HandoverPoint
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($missingChild->province_id ?? $missingChild->last_seen_province_id);

        return view('missing.detail', $data);
    }
    public function create()
    {
        return view('missing.create');
    }

    public function store(StoreMissingChildRequest $request)
    {
    }

    public function detailByQuery(\Illuminate\Http\Request $request)
    {
        if ($request->has('case_code')) {
            $data = $this->missingChildService->getDetailForPublicByCaseCode($request->case_code);
        } elseif ($request->has('id')) {
            $data = $this->missingChildService->getDetailForPublic((int) $request->id);
        } else {
            abort(404);
        }

        // Lấy danh sách HandoverPoint
        $provinceId = $data['case']->province_id ?? $data['case']->last_seen_province_id ?? null;
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($provinceId);

        return view('missing.detail', $data);
    }

    public function resolvedShow(int $id)
    {
        $data = $this->missingChildService->getResolvedForPublic($id);

        return view('missing.resolved', $data);
    }

    /**
     * H-03 — Xác nhận đoàn tụ (chỉ chủ tài khoản).
     *
     * - Middleware auth + email.verified (khai báo ở routes/web.php).
     * - Policy confirmReunited: chỉ owner_id === auth()->id().
     * - Idempotent: đã REUNITED → 409.
     * - Ghi status=REUNITED, reunited_confirmed_at/by, method=FAMILY_SELF.
     *   Observer StatusChangeAuditObserver tự ghi audit_logs (actor = FAMILY).
     * - Tự động biến mất khỏi trang chủ/map vì các query lấy danh sách khẩn cấp
     *   đều lọc theo status (REUNITED nằm trong CLOSED_STATUSES).
     */
    public function confirmReunited(Request $request, MissingChild $missingChild)
    {
        $this->authorize('confirmReunited', $missingChild);

        if ($missingChild->isReunited()) {
            abort(409, 'Hồ sơ này đã được xác nhận đoàn tụ trước đó.');
        }

        $missingChild->forceFill([
            'status' => MissingChild::STATUS_REUNITED,
            'reunited_confirmed_at' => now(),
            'reunited_confirmed_by' => $request->user()->id,
            'reunited_verification_method' => MissingChild::REUNION_METHOD_FAMILY_SELF,
        ])->save();

        return redirect()
            ->route('missing.detail', ['id' => $missingChild->id])
            ->with('success', 'Chúc mừng! Hồ sơ đã được xác nhận đoàn tụ thành công.');
    }

    /**
     * H-05 — Gia đình xác nhận "bé đã được bàn giao" (HANDED_OVER).
     *
     * - Middleware auth (khai báo ở routes/web.php); cổng KYC do Policy::confirmHandover
     *   thay cho email.verified (gia đình đã KYC khi đăng tin nên không cần verify email).
     * - Policy confirmHandover: chỉ owner_id === auth()->id() và đã KYC.
     * - Idempotent: đã HANDED_OVER/REUNITED → 409.
     * - Ghi status=HANDED_OVER, handover_confirmed_at/by. StatusChangeAuditObserver tự
     *   ghi audit_logs (actor = FAMILY). Observer MissingChildObserver gửi thông báo cho
     *   người tìm thấy (TYPE_FOUND_CASE_HANDED_OVER).
     * - Tự động biến mất khỏi trang chủ/map vì các query lấy danh sách khẩn cấp lọc status.
     */
    public function confirmHandover(Request $request, MissingChild $missingChild)
    {
        $this->authorize('confirmHandover', $missingChild);

        if ($missingChild->isHandedOver() || $missingChild->isReunited()) {
            abort(409, 'Hồ sơ này đã được xác nhận bàn giao hoặc đoàn tụ trước đó.');
        }

        // Snapshot trạng thái trước khi bước sang HANDED_OVER để luồng "phủ nhận
        // kết quả khớp" (denyHandover) có thể revert về đúng trạng thái công khai
        // trước đó (FOUND/APPROVED). KHÔNG thay đổi hành vi chuyển trạng thái.
        $missingChild->missing_child_prev_status = $missingChild->status;

        $missingChild->forceFill([
            'status' => MissingChild::STATUS_HANDED_OVER,
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => $request->user()->id,
        ])->save();

        return redirect()
            ->route('missing.detail', ['id' => $missingChild->id])
            ->with('success', 'Cảm ơn bạn! Hệ thống đã ghi nhận bé đã được bàn giao. Gia đình sẽ sớm đón bé về.');
    }

    /**
     * H-05 (Việc 2) — Gia đình phủ nhận kết quả khớp ("Không phải con tôi") ở bước
     * HANDED_OVER. Chỉ hiển thị khi status = HANDED_OVER (xem family-handover-card).
     *
     * - Tái dùng MissingChildCascadeService::handleRemoval (y nguyên luồng Từ chối/Xoá):
     *   FaceMatchResult CONFIRMED → DISMISSED, FoundChild revert về trạng thái trước.
     * - MissingChild revert về missing_child_prev_status đã snapshot lúc confirm
     *   (FOUND/APPROVED), fallback APPROVED nếu null. Clear cờ handover xác nhận.
     * - Ghi audit_reason rõ ràng "Gia đình phủ nhận kết quả khớp #<id>" để dễ truy vết
     *   (StatusChangeAuditObserver tự ghi AuditLog với actor = FAMILY).
     * - Idempotent: đã APPROVED (đã revert) hoặc REUNITED → 409.
     * - KHÔNG tự động revert theo thời gian (giữ UNREACHABLE_FAMILY cho admin xử lý).
     */
    public function denyHandover(Request $request, MissingChild $missingChild)
    {
        $this->authorize('confirmHandover', $missingChild);

        if ($missingChild->status === MissingChild::STATUS_APPROVED
            || $missingChild->status === MissingChild::STATUS_REUNITED) {
            abort(409, 'Hồ sơ này không ở trạng thái có thể phủ nhận kết quả khớp.');
        }

        if ($missingChild->status !== MissingChild::STATUS_HANDED_OVER) {
            abort(409, 'Chỉ hồ sơ đang ở trạng thái xác nhận bàn giao mới được phủ nhận.');
        }

        $match = $missingChild->faceMatchResults()
            ->where('status', 'CONFIRMED')
            ->with('foundChild')
            ->first();

        $matchId = $match?->getKey();

        // Ghi lý do tường minh trước khi save() để StatusChangeAuditObserver lưu vào AuditLog.
        $missingChild->audit_reason = 'Gia đình phủ nhận kết quả khớp #' . ($matchId ?? 'n/a');

        app(\App\Services\MissingChildCascadeService::class)->handleRemoval($missingChild, includePending: true);

        $prev = $missingChild->missing_child_prev_status ?? MissingChild::STATUS_APPROVED;

        $missingChild->forceFill([
            'status' => $prev,
            'handover_confirmed_at' => null,
            'handover_confirmed_by' => null,
            'handover_point_id' => null,
        ])->save();

        return redirect()
            ->route('missing.detail', ['id' => $missingChild->id])
            ->with('success', 'Đã gỡ kết quả đối chiếu. Hồ sơ quay lại trạng thái tìm kiếm công khai.');
    }
    /**
     * Hàm phụ trợ lấy HandoverPoint hoạt động theo Tỉnh/Thành
     */
    private function getHandoverPointsByProvince(?int $provinceId)
    {
        return HandoverPoint::where('is_active', true)
            ->when($provinceId, function ($query) use ($provinceId) {
                return $query->where('province_id', $provinceId);
            })
            ->orderBy('priority_order', 'asc')
            ->get();
    }
}
