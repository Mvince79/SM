<?php

namespace App\Support;

use App\Models\NotificationEvent;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Báo lỗi xử lý AI / eKYC (ProcessPhotoJob, TriggerFaceMatchJob, ProcessKycJob)
 * cho admin/moderator qua đường ống thông báo hợp nhất.
 *
 * Admin nhận cả chuông lẫn email theo policy TYPE_AI_PROCESSING_FAILED — không
 * còn gọi $user->notify() hệ cũ (bảng `notifications`), để giữ một hệ thống duy
 * nhất, một bảng, và badge realtime cập nhật được.
 */
class AIJobFailureNotifier
{
    public static function notifyAdmins(Model $subject, string $jobName, string $message): void
    {
        try {
            $notifier = app(Notifier::class);
            $recipients = app(RecipientResolver::class)->admins();

            $notifier->send(
                NotificationEvent::TYPE_AI_PROCESSING_FAILED,
                $subject,
                $recipients,
                [
                    'job' => $jobName,
                    'message' => $message,
                    'source' => NotificationEvent::SOURCE_SYSTEM,
                ],
                null,
            );
        } catch (\Throwable $e) {
            // Việc báo lỗi không được làm chết luồng xử lý chính của job.
            Log::error('Không gửi được thông báo lỗi AI cho admin', [
                'subject' => $subject->getMorphClass() . '#' . $subject->getKey(),
                'job' => $jobName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
