<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AIProcessingFailedNotification;

class AIJobFailureNotifier
{
    public static function notifyAdmins(int $photoId, string $jobName, \Throwable $exception): void
    {
        User::query()
            ->admins()
            ->get()
            ->each(fn (User $user) => $user->notify(
                new AIProcessingFailedNotification($photoId, $jobName, $exception->getMessage())
            ));
    }
}
