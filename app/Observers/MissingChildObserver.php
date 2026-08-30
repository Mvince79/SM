<?php

namespace App\Observers;

use App\Jobs\DispatchProvinceCaseApprovedNotificationsJob;
use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissingChildObserver
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly RecipientResolver $recipients,
    ) {
    }

    private function invalidateMapAndHomeCache(): void
    {
        try {
            CacheVersion::bump(CacheVersion::MAP_DATA);
            CacheVersion::bump(CacheVersion::HOME_HIGHLIGHTS);
        } catch (\Throwable $e) {
            Log::warning('MissingChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the MissingChild "creating" event.
     * Chạy TRƯỚC khi INSERT → tránh race condition sinh code trùng.
     */
    public function creating(MissingChild $missingChild): void
    {
        $missingChild->case_code = $this->generateCaseCode();
    }

    /**
     * Sinh case_code duy nhất, thread-safe nhờ lockForUpdate bên trong transaction.
     */
    private function generateCaseCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "MC-{$year}-";

            // lockForUpdate giờ có tác dụng vì record chưa tồn tại trong DB,
            // nên 2 request chạy song song sẽ lần lượt nhau, không đọc cùng maxCode.
            $maxCode = DB::table('missing_children')
                ->where('case_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->max('case_code');

            $sequence = $maxCode
                ? (int) substr($maxCode, -5) + 1
                : 1;

            return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Handle the MissingChild "created" event.
     * Chỉ còn log, không cần tạo case_code ở đây nữa.
     *
     * F-01 — Không gán review_level vào missing_children: cột review_level
     * thuộc bảng kyc_verifications (được ghi bởi ProcessKycJob), không tồn tại
     * trên missing_children. Việc gán trước đây ném ngoại lệ và bị nuốt bởi
     * try/catch, nên đã được gỡ bỏ.
     */
    public function created(MissingChild $missingChild): void
    {
        Log::info("MissingChild Observer (created): Generated case_code {$missingChild->case_code} for child ID {$missingChild->id}");

        // Tin mới (kể cả đang PENDING chờ duyệt) có thể đổi số liệu thống
        // kê ở trang chủ, và nếu status ban đầu đã là APPROVED thì cũng
        // cần xuất hiện ngay trên bản đồ — không đợi cache cũ hết TTL.
        $this->invalidateMapAndHomeCache();

        // Generate QR code PNG pointing to public detail URL and save path to model
        try {
            $qrService = app(\App\Services\QrCodeService::class);
            // Per spec: QR should point to /missing/{case_code}
            $detailUrl = url('/missing/' . $missingChild->case_code);
            $path = $qrService->generate($detailUrl, $missingChild->case_code);

            // Save relative path into model without firing events
            $missingChild->qr_code_path = $path;
            $missingChild->saveQuietly();
            Log::info("MissingChild Observer: QR code generated at {$path} for {$missingChild->case_code}");
        } catch (\Throwable $e) {
            Log::warning('QR generation failed: ' . $e->getMessage());
        }

        // D-05 bổ sung: báo admin có tin tìm trẻ mới chờ duyệt.
        // Độc lập với QR/cache/log — nằm trong try riêng để không ảnh hưởng hành vi cũ.
        try {
            $admins = $this->recipients->admins();
            if ($admins->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_CASE_SUBMITTED,
                    $missingChild,
                    $admins,
                    [
                        'case_code' => $missingChild->case_code,
                        'full_name' => $missingChild->full_name,
                    ],
                    null,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('CASE_SUBMITTED notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the MissingChild "updated" event.
     */
    public function updated(MissingChild $missingChild): void
    {
        if ($missingChild->wasChanged('status') && $missingChild->status === 'APPROVED') {
            DispatchProvinceCaseApprovedNotificationsJob::dispatch($missingChild->getKey());
        }

        // Đánh dấu "Đã bàn giao" → thông báo cho gia đình/chủ hồ sơ đến nhận lại con
        // VÀ cho người tìm thấy bé (cảm ơn vì đã bàn giao).
        // Pattern tương tự nhánh APPROVED ở trên: observer phát hiện đổi status.
        // - TYPE_HANDOVER_READY gửi gia đình CHỈ KHI actor là ADMIN (admin báo gia
        //   đình đến nhận). Nếu gia đình tự xác nhận (actor=FAMILY) thì không tự gửi
        //   thông báo cho chính họ.
        // - TYPE_FOUND_CASE_HANDED_OVER luôn gửi cho người tìm thấy (dù ai xác nhận).
        if ($missingChild->wasChanged('status') && $missingChild->status === MissingChild::STATUS_HANDED_OVER) {
            $isAdminActor = Auth::user()?->isAdmin() ?? false;

            if ($isAdminActor) {
                $family = $this->recipients->familyOf($missingChild);

                if ($family) {
                    $this->notifier->send(
                        NotificationEvent::TYPE_HANDOVER_READY,
                        $missingChild,
                        [$family],
                        [
                            'case_code' => $missingChild->case_code,
                            'full_name' => $missingChild->full_name,
                        ],
                        $missingChild->handover_confirmed_by,
                    );
                }
            }

            // Người tìm thấy nhận thông báo "trẻ bạn báo đã được bàn giao".
            $foundPosters = $this->foundPosterUsers($missingChild);
            if ($foundPosters->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER,
                    $missingChild,
                    $foundPosters,
                    [
                        'case_code' => $missingChild->case_code,
                        'full_name' => $missingChild->full_name,
                    ],
                    $missingChild->handover_confirmed_by,
                );
            }
        }

        // Xác nhận "Đã đoàn tụ" → báo cho admin/moderator đang active (hồ sơ đã đóng).
        // Song song với các nhánh trên: observer phát hiện đổi status. Tái dùng
        // RecipientResolver::admins() (admin/moderator đang active) — đã dùng ở
        // luồng So khớp AI (Item 3).
        if ($missingChild->wasChanged('status') && $missingChild->status === MissingChild::STATUS_REUNITED) {
            $admins = $this->recipients->admins();

            if ($admins->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_REUNION_CONFIRMED,
                    $missingChild,
                    $admins,
                    [
                        'case_code' => $missingChild->case_code,
                        'full_name' => $missingChild->full_name,
                    ],
                    null,
                );
            }

            // Người tìm thấy nhận thông báo "trẻ đã về với gia đình — cảm ơn bạn".
            $foundPosters = $this->foundPosterUsers($missingChild);
            if ($foundPosters->isNotEmpty()) {
                $this->notifier->send(
                    NotificationEvent::TYPE_FOUND_CASE_REUNITED,
                    $missingChild,
                    $foundPosters,
                    [
                        'case_code' => $missingChild->case_code,
                        'full_name' => $missingChild->full_name,
                    ],
                    null,
                );
            }

            // Tự động đóng các EscalationQueue đang chờ liên quan đến hồ sơ này.
            // escalation_queue không lưu subject trực tiếp mà qua
            // notification_recipient -> notification_events(subject_type/subject_id),
            // nên tra qua relation đó (đúng cách EscalateUnconfirmedReunionJob tạo row).
            // Khi hồ sơ đã đoàn tụ, không còn hiển thị "cần hỗ trợ khẩn cấp" trên dashboard.
            EscalationQueue::query()
                ->where('status', EscalationQueue::STATUS_PENDING)
                ->whereHas('notificationRecipient', function ($query) use ($missingChild) {
                    $query->whereHas('event', function ($query) use ($missingChild) {
                        $query->where('subject_type', $missingChild->getMorphClass())
                            ->where('subject_id', $missingChild->getKey());
                    });
                })
                ->update([
                    'status' => EscalationQueue::STATUS_RESOLVED,
                    'resolved_at' => now(),
                ]);
        }

        // Từ chối hồ sơ → báo người đăng (kèm lý do). D-05 bổ sung.
        // Bao phủ cả nút "Từ chối" đơn, trang chi tiết và "Từ chối hàng loạt".
        if ($missingChild->wasChanged('status') && $missingChild->status === MissingChild::STATUS_REJECTED) {
            $owner = $missingChild->user;

            // Phòng vệ: getEloquentQuery() của resource có thể eager-load user chỉ
            // với vài cột (id,full_name), khiến $owner->role = null. deliver() lấy
            // $user->role đắp vào role_at_time (cột NOT NULL) → nếu null sẽ ném lỗi
            // và recipient không bao giờ được tạo (chuông admin không báo). Nạp lại
            // user đầy đủ để đảm bảo role có mặt trước khi gửi.
            if ($owner && $owner->role === null) {
                $owner = User::find($owner->getKey());
            }

            if ($owner) {
                $this->notifier->send(
                    NotificationEvent::TYPE_CASE_REJECTED,
                    $missingChild,
                    [$owner],
                    [
                        'reason' => $missingChild->rejection_reason,
                        'case_code' => $missingChild->case_code,
                        'full_name' => $missingChild->full_name,
                    ],
                    null,
                );
            }
        }

        if ($missingChild->wasChanged(['status', 'lat', 'lng'])) {
            $this->invalidateMapAndHomeCache();
        }

        try {
            activity()
                ->performedOn($missingChild)
                ->event('updated')
                ->withProperties([
                    'old' => array_intersect_key($missingChild->getOriginal(), $missingChild->getChanges()),
                    'attributes' => $missingChild->getChanges(),
                ])
                ->log("Đã cập nhật tin tìm kiếm trẻ thất lạc: {$missingChild->full_name} ({$missingChild->case_code})");
        } catch (\Throwable $e) {
            Log::warning('Activity log failed: ' . $e->getMessage());
        }

        Log::info("MissingChild Observer (updated): Updated child ID {$missingChild->id} ({$missingChild->case_code})");
    }

    /**
     * Handle the MissingChild "deleted" event (soft delete).
     */
    public function deleted(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }

    /**
     * Handle the MissingChild "restored" event.
     */
    public function restored(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }

    /**
     * H-05 — Lấy danh sách user đăng tin TÌM THẤY liên quan đến hồ sơ mất tích này
     * qua các FaceMatchResult đã CONFIRMED (khớp được xác nhận). Dùng để gửi thông
     * báo cho người tìm thấy khi hồ sơ chuyển HANDED_OVER / REUNITED.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function foundPosterUsers(MissingChild $missingChild): \Illuminate\Support\Collection
    {
        return $missingChild->faceMatchResults()
            ->where('status', 'CONFIRMED')
            ->with('foundChild.user')
            ->get()
            ->map(fn ($match) => $match->foundChild?->user)
            ->filter()
            ->unique('id')
            ->values();
    }
}
