<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    /** Các trạng thái được coi là "override thủ công" cần lý do từ admin. */
    private const ADMIN_OVERRIDE_STATUSES = [
        MissingChild::STATUS_REUNITED,
        MissingChild::STATUS_UNREACHABLE_FAMILY,
        'REUNITED',
        'UNREACHABLE_FAMILY',
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
