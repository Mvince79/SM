<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * I-06 — Logic đổi role an toàn.
 *
 * Nguyên tắc: role (phân quyền) và password (xác thực) độc lập nhau.
 * Đổi role KHÔNG BAO GIỜ được đụng vào cột password.
 *
 * Việc thu hồi token thực sự được thực hiện ở UserObserver::updated()
 * (chạy cho MỌI đường cập nhật role: Filament, Livewire, tinker, service này...).
 * Class này chỉ là entrypoint tường minh cho code gọi tay + nơi ghi log
 * "ai đổi role của ai" (khác với log kỹ thuật "token nào bị thu hồi" ở Observer).
 */
class UserRoleChangeService
{
    public function changeRole(User $user, string $newRole, User $admin): User
    {
        $oldRole = $user->role;

        if ($oldRole === $newRole) {
            return $user;
        }

        return DB::transaction(function () use ($user, $newRole, $oldRole, $admin) {
            $user->role = $newRole;
            $user->save(); // -> UserObserver::updated() tự thu hồi token cũ

            $this->logRoleChange($user, $admin, $oldRole, $newRole);

            return $user->fresh();
        });
    }

    private function logRoleChange(User $user, User $admin, string $oldRole, string $newRole): void
    {
        if (function_exists('activity')) {
            // Dùng khi project đã cài spatie/laravel-activitylog
            activity()
                ->causedBy($admin)
                ->performedOn($user)
                ->withProperties([
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ])
                ->log('role_changed');

            return;
        }

        // Fallback: chưa cài activitylog thì ghi log file, vẫn đủ audit trail tối thiểu
        Log::info('role_changed', [
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);
    }
}