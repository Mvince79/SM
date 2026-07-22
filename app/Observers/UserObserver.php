<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * I-06 — Đổi role an toàn.
 *
 * Đặt logic thu hồi token ở Observer (không chỉ ở Service) để đảm bảo
 * NGƯỜI NÀO/CHỖ NÀO cập nhật $user->role (Filament resource của I-07,
 * tinker, UserRoleChangeService, ...) đều tự động bị áp dụng, không thể
 * quên gọi.
 */
class UserObserver
{
    /**
     * `updated` chạy SAU khi DB đã ghi role mới thành công, nhưng TRƯỚC khi
     * syncOriginal() — nên wasChanged('role') và getOriginal('role') (giá trị CŨ)
     * vẫn dùng đúng ở đây.
     */
    public function updated(User $user): void
    {
        if (!$user->wasChanged('role')) {
            return;
        }

        $oldRole = $user->getOriginal('role');
        $newRole = $user->role;

        $tokenCount = $user->tokens()->count();
        $user->tokens()->delete();

        Log::info('User role changed - all tokens revoked', [
            'user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'tokens_revoked' => $tokenCount,
        ]);
    }
}