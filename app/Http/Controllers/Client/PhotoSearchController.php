<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use App\Services\FaceMatchService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * S — Tìm kiếm bằng hình ảnh (AI Photo Search, công khai).
 *
 * - index():  trang /tim-kiem-bang-anh (S-01, public).
 * - search(): POST /api/photo-search (S-02, throttle:5,60). So khớp ảnh upload
 *             với toàn bộ hồ sơ trẻ mất tích chưa đoàn tụ, trả tối đa 5 kết quả
 *             score >= 0.5 (cấu hình face_match), không trả địa chỉ/SĐT.
 */
class PhotoSearchController extends Controller
{
    public function __construct(
        private FaceMatchService $faceMatchService
    ) {
    }

    /** S-01 — Trang tìm kiếm bằng ảnh công khai. */
    public function index()
    {
        // Danh sách tỉnh/thành phố cho bộ lọc (chuẩn châu Âu, tối ưu mobile).
        $provinces = Province::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('photo-search.index', compact('provinces'));
    }

    /**
     * S-02 — API so khớp ảnh với toàn bộ hồ sơ.
     * Response mỗi kết quả: case_code, tên, tuổi ước tính, ảnh đại diện, score (%) —
     * KHÔNG chứa địa chỉ/SĐT gia đình.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'gender' => ['sometimes', 'nullable', 'in:MALE,FEMALE,UNKNOWN'],
            'age_min' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'age_max' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'province_id' => ['sometimes', 'nullable', 'integer', 'exists:provinces,id'],
        ]);

        $tmpPath = null;

        try {
            // Lưu ảnh tạm để gửi sang AI encode.
            $tmpPath = $data['photo']->store('photo-search-tmp', 'public');

            $encoded = $this->faceMatchService->encode(
                $tmpPath,
                'public'
            );

            if (empty($encoded['encoding']) || $encoded['has_face'] === false) {
                return response()->json([
                    'status' => 'NO_FACE',
                    'message' => 'Không tìm thấy khuôn mặt trong ảnh. Vui lòng thử ảnh khác.',
                    'results' => [],
                ]);
            }

            $queryEncoding = $encoded['encoding'];
            $threshold = (float) config('face_match.search_threshold', 0.5);

            // Lấy toàn bộ ảnh hồ sơ trẻ mất tích chưa đoàn tù (có face_encoding),
            // đã áp dụng bộ lọc giao diện (giới tính / độ tuổi / tỉnh) để thu nhỏ
            // tập ứng viên trước khi so khớp — tối ưu cả băng thông lẫn thời gian.
            // Lưu ý: 'model' là quan hệ morphTo (cả MissingChild & FoundChild), nên
            // KHÔNG dùng whereHas('model', ...) — nó sẽ sinh subquery cho CẢ 2 bảng và
            // gãy ở cột không tồn tại (vd: found_children không có 'gender'). Thay vào
            // đó dùng whereExists scoped đúng vào bảng missing_children.
            $candidatePhotos = Photo::query()
                ->where('model_type', MissingChild::class)
                ->where('has_face', 1)
                ->whereNotNull('face_encoding')
                ->with('model')
                ->whereExists(function ($sub) use ($request) {
                    $sub->select(DB::raw('1'))
                        ->from('missing_children')
                        ->whereColumn('missing_children.id', 'photos.model_id');

                    if ($request->filled('gender')) {
                        $sub->where('missing_children.gender', $request->input('gender'));
                    }
                    if ($request->filled('age_min')) {
                        $sub->where('missing_children.age_estimate', '>=', (int) $request->input('age_min'));
                    }
                    if ($request->filled('age_max')) {
                        $sub->where('missing_children.age_estimate', '<=', (int) $request->input('age_max'));
                    }
                    if ($request->filled('province_id')) {
                        $sub->where('missing_children.province_id', (int) $request->input('province_id'));
                    }
                })
                ->get();

            $resultsByChild = [];

            foreach ($candidatePhotos as $photo) {
                $missing = $photo->model;

                if (!$missing instanceof MissingChild) {
                    continue;
                }

                // Bỏ qua hồ sơ đã đóng/đoàn tụ.
                if (in_array($missing->status, MissingChild::CLOSED_STATUSES, true)) {
                    continue;
                }

                try {
                    $candidateEncoding = json_decode(Crypt::decryptString($photo->face_encoding), true);
                } catch (\Throwable) {
                    continue;
                }

                // face_encoding lưu dưới dạng Crypt::encryptString(json_encode([...])).
                // Giải mã xong vẫn là chuỗi JSON → phải json_decode thành array trước
                // khi truyền cho compare() (hàm yêu cầu array).
                if (!is_array($candidateEncoding)) {
                    continue;
                }

                $comparison = $this->faceMatchService->compare($queryEncoding, $candidateEncoding);
                $score = (float) ($comparison['score'] ?? 0);

                if ($score < $threshold) {
                    continue;
                }

                $childId = $missing->id;
                $item = [
                    'case_code' => $missing->case_code,
                    'name' => $missing->full_name,
                    'age' => $missing->age,
                    'photo_url' => $missing->thumb_url,
                    'missing_child_id' => $missing->id,
                    'score' => round($score * 100, 2),
                ];

                // Gộp theo trẻ: nếu trẻ này đã có kết quả trước đó (do nhiều ảnh
                // cùng khớp), chỉ giữ lại bản có score cao nhất — tránh hiển thị
                // trùng lặp cùng 1 trẻ nhiều lần trên trang kết quả.
                if (!isset($resultsByChild[$childId]) || $item['score'] > $resultsByChild[$childId]['score']) {
                    $resultsByChild[$childId] = $item;
                }
            }

            $results = array_values($resultsByChild);

            // Sắp theo score giảm dần.
            usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

            // Phân trang (tối ưu UI/UX khi có nhiều hồ sơ): so khớp vẫn duyệt toàn
            // bộ ứng viên, nhưng chỉ trả về đúng 1 trang để giảm payload. Ngưỡng
            // hiển thị đã lọc ở trên (search_threshold, mặc định 0.45).
            $perPage = (int) ($request->input('per_page', 6));
            $page = (int) ($request->input('page', 1));
            $total = count($results);
            $paginator = new LengthAwarePaginator(
                $results,
                $total,
                $perPage,
                $page,
                ['path' => route('api.photo-search')]
            );

            return response()->json([
                'status' => 'OK',
                'results' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Đã xảy ra lỗi khi so khớp. Vui lòng thử lại sau.',
                'results' => [],
            ], 502);
        } finally {
            // Xóa file ảnh tạm ngay sau khi xử lý xong.
            if ($tmpPath && Storage::disk('public')->exists($tmpPath)) {
                Storage::disk('public')->delete($tmpPath);
            }
        }
    }
}
