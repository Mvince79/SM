<?php

namespace App\Notifications;

use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use UnexpectedValueException;

class RoleChangedNotification implements NotificationStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        $subject = $event->subject;

        if (! $subject instanceof User || ! $subject->is($recipient)) {
            throw new UnexpectedValueException(
                'ROLE_CHANGED yêu cầu subject và recipient là cùng một User.'
            );
        }

        $oldRole = $this->roleLabel((string) ($event->payload['old_role'] ?? ''));
        $newRole = $this->roleLabel((string) ($event->payload['new_role'] ?? $recipient->role));

        return [
            'title' => 'Quyền truy cập tài khoản đã thay đổi',
            'body' => "Vai trò của bạn đã được đổi từ {$oldRole} sang {$newRole}. "
                .'Vui lòng đăng nhập lại để áp dụng quyền mới.',
            'action_url' => route('auth.login'),
        ];
    }

    private function roleLabel(string $role): string
    {
        return match (strtoupper($role)) {
            'USER' => 'Người dùng',
            'VOLUNTEER' => 'Tình nguyện viên',
            'THIRD_PARTY_VERIFIER' => 'Bên thứ ba xác minh',
            'MODERATOR' => 'Kiểm duyệt viên',
            'ADMIN' => 'Quản trị viên',
            default => $role,
        };
    }
}
