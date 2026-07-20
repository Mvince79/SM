<?php

use App\Models\AdminChatAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'private-chat.{userId}',
    function ($user, $userId) {
        return (int) $user->id === (int) $userId;
    }
);

Broadcast::channel('missing-child.{missingId}', function () {
    return true;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel(
    'admin-chat-assignment.{assignmentId}',
    function (User $user, int $assignmentId): bool {
        if (! $user->isAdmin()) {
            return false;
        }

        return AdminChatAssignment::query()
            ->whereKey($assignmentId)
            ->where('admin_user_id', $user->getKey())
            ->where('status', AdminChatAssignment::STATUS_ACTIVE)
            ->exists();
    },
);
