<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Notifier;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * D-01 — Ghi audit_logs mỗi khi status của MissingChild / FoundChild thay đổi.
 *
 * Actor:
 *  - Auth::user() là admin  -> actor_role = ADMIN
 *  - Auth::user() thường    -> actor_role = FAMILY (chủ hồ sơ)
 *  - Không có user (job)    -> actor_role = SYSTEM
 *
 * `reason` bắt buộc (min 20 ký tự) với override thủ công của ADMIN — caller
 * (vd: Filament action xác nhận đoàn tụ hộ) set `$model->audit_reason` trước
 * khi save; observer đọc và từ chối lưu nếu thiếu/quá ngắn (ném ValidationException
 * để rollback transaction của request đó).
 */
class StatusChangeAuditObserver
{
    public function __construct(
        private readonly Notifier $notifier,
    ) {
    }

    /** Các trạng thái được coi là "override thủ công" cần lý do từ admin. */
    private const ADMIN_OVERRIDE_STATUSES = [
        MissingChild::STATUS_REUNITED,
        MissingChild::STATUS_UNREACHABLE_FAMILY,
        MissingChild::STATUS_HANDED_OVER,
        'REUNITED',
        'UNREACHABLE_FAMILY',
        'HANDED_OVER',
    ];

    public function updated(MissingChild|FoundChild $model): void
    {
        if (! $model->isDirty('status')) {
            return;
        }

        $actor = Auth::user();
        $actorRole = $this->resolveActorRole($actor);
        $newStatus = $model->status;

        $reason = $model->audit_reason ?? null;

        $minLength = MissingChild::ADMIN_REASON_MIN_LENGTH;

        if ($actorRole === 'ADMIN' && in_array($newStatus, self::ADMIN_OVERRIDE_STATUSES, true)) {
            if (blank($reason) || mb_strlen((string) $reason) < $minLength) {
                throw new \Illuminate\Validation\ValidationException(
                    validator: \Illuminate\Support\Facades\Validator::make(
                        ['reason' => $reason],
                        ['reason' => 'required|min:' . $minLength],
                    ),
                );
            }
        }

        $request = $this->resolveRequest();

        AuditLog::create([
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_role' => $actorRole,
            'action' => $model->getTable() . '.status_updated',
            'target_type' => $model->getTable(),
            'target_id' => $model->getKey(),
            'old_value' => ['status' => $model->getOriginal('status')],
            'new_value' => ['status' => $newStatus],
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * D-01 bổ sung — Ghi audit_logs khi một hồ sơ bị xoá (soft-delete).
     *
     * Trước đây observer chỉ bắt `updated()` nên hành động Xoá (cả FoundChild
     * lẫn MissingChild) KHÔNG xuất hiện trong Nhật Ký Kiểm Toán, dù vẫn có mặt
     * ở Nhật Ký Hoạt Động (spatie activity log). Sự kiện `deleted` của Eloquent
     * vẫn giữ nguyên các attribute khác (kể cả `status`) nên ta lấy trạng thái
     * trước xoá làm `old_value.status`, còn `new_value.status` = 'DELETED'
     * (Labels::status() dịch thành "Đã xoá").
     *
     * Xoá hiện tại KHÔNG có ô nhập lý do ở UI → `reason` để trống (null),
     * không tự bịa. Vẫn đọc `audit_reason` phòng thủ (MissingChild có property
     * này) để tương lai nếu caller set lý do trước khi xoá thì được ghi nhận.
     */
    public function deleted(MissingChild|FoundChild $model): void
    {
        $this->writeDeletionAudit($model);
    }

    /**
     * Tương tự `deleted()` nhưng cho xoá vĩnh viễn (ForceDeleteAction của
     * MissingChild trên các hồ sơ đã nằm trong thùng rác). forceDelete() của
     * Eloquent chỉ bắn `forceDeleted`, không bắn `deleted`, nên hai sự kiện này
     * loại trừ lẫn nhau — không sinh bản ghi audit trùng.
     */
    public function forceDeleted(MissingChild|FoundChild $model): void
    {
        $this->writeDeletionAudit($model);
    }

    /**
     * D-01 bổ sung — Ghi audit_logs + báo chủ hồ sơ khi một hồ sơ bị KHÔI PHỤC
     * (từ thùng rác, sự kiện `restored` của SoftDeletes).
     *
     * Nhật Ký Hoạt Động (spatie activity_log) đã tự ghi 'restored' nhờ trait
     * LogsActivity + SoftDeletes (xem vendor spatie), nên ở đây chỉ xử lý Nhật
     * Ký Kiểm Toán và thông báo. old_value.status = 'DELETED' (Labels dịch
     * "Đã xoá"), new_value.status = trạng thái thực của hồ sơ (soft-delete
     * không đổi status nên đây chính là trạng thái trước khi xoá).
     *
     * QUAN TRỌNG: KHÔNG gọi cascade revert — FaceMatchResult đã bị DISMISSED lúc
     * xoá vẫn giữ nguyên, admin phải tự xem xét và xác nhận lại match nếu cần.
     */
    public function restored(MissingChild|FoundChild $model): void
    {
        $actor = Auth::user();
        $actorRole = $this->resolveActorRole($actor);
        $request = $this->resolveRequest();

        AuditLog::create([
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_role' => $actorRole,
            'action' => $model->getTable() . '.status_restored',
            'target_type' => $model->getTable(),
            'target_id' => $model->getKey(),
            'old_value' => ['status' => 'DELETED'],
            'new_value' => ['status' => $model->status],
            'reason' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        // Báo chủ hồ sơ biết hồ sơ đã được khôi phục (sự kiện riêng với Xoá/Từ chối).
        // Tái dùng pattern guard `$owner->role === null` y hệt các nhánh trước.
        $owner = $model->user;

        if ($owner && $owner->role === null) {
            $owner = User::find($owner->getKey());
        }

        if ($owner) {
            $eventType = $model instanceof MissingChild
                ? NotificationEvent::TYPE_CASE_RESTORED
                : NotificationEvent::TYPE_FOUND_RESTORED;

            $payload = $model instanceof MissingChild
                ? ['case_code' => $model->case_code, 'full_name' => $model->full_name]
                : [];

            try {
                $this->notifier->send(
                    $eventType,
                    $model,
                    [$owner],
                    $payload,
                    $actor?->getAuthIdentifier(),
                );
            } catch (\Throwable $e) {
                Log::warning('StatusChangeAuditObserver: gửi thông báo khôi phục hồ sơ thất bại: ' . $e->getMessage());
            }
        }
    }

    private function writeDeletionAudit(MissingChild|FoundChild $model): void
    {
        $actor = Auth::user();
        $actorRole = $this->resolveActorRole($actor);
        $reason = data_get($model, 'audit_reason');
        $request = $this->resolveRequest();

        AuditLog::create([
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_role' => $actorRole,
            'action' => $model->getTable() . '.status_deleted',
            'target_type' => $model->getTable(),
            'target_id' => $model->getKey(),
            'old_value' => ['status' => $model->status],
            'new_value' => ['status' => 'DELETED'],
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        // Báo chủ hồ sơ biết hồ sơ của họ bị xoá (sự kiện riêng với Từ chối —
        // không có :reason vì UI xoá không có ô nhập lý do). Tái dùng pattern
        // guard `$owner->role === null` y hệt FoundChildObserver/MissingChildObserver
        // ở nhánh REJECTED: getEloquentQuery() resource có thể eager-load user
        // chỉ vài cột khiến role = null → deliver() insert NULL vào role_at_time
        // (cột NOT NULL) → recipient không tạo được. Nạp lại user đầy đủ trước
        // khi gửi. Bọc try/catch để lỗi thông báo KHÔNG rollback luồng xoá
        // (delete đang nằm trong DB::transaction ở Filament action).
        $owner = $model->user;

        if ($owner && $owner->role === null) {
            $owner = User::find($owner->getKey());
        }

        if ($owner) {
            $eventType = $model instanceof MissingChild
                ? NotificationEvent::TYPE_CASE_DELETED
                : NotificationEvent::TYPE_FOUND_DELETED;

            $payload = $model instanceof MissingChild
                ? ['case_code' => $model->case_code, 'full_name' => $model->full_name]
                : [];

            try {
                $this->notifier->send(
                    $eventType,
                    $model,
                    [$owner],
                    $payload,
                    $actor?->getAuthIdentifier(),
                );
            } catch (\Throwable $e) {
                Log::warning('StatusChangeAuditObserver: gửi thông báo xoá hồ sơ thất bại: ' . $e->getMessage());
            }
        }
    }

    private function resolveActorRole(?Authenticatable $actor): ?string
    {
        if (! $actor instanceof User) {
            return 'SYSTEM';
        }

        return $actor->isAdmin() ? 'ADMIN' : 'FAMILY';
    }

    private function resolveRequest(): ?Request
    {
        try {
            return app(Request::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
