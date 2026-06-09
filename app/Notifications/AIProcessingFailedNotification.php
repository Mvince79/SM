<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AIProcessingFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $photoId,
        public string $jobName,
        public string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ai_processing_failed',
            'photo_id' => $this->photoId,
            'job' => $this->jobName,
            'title' => 'AI processing failed',
            'message' => "Photo #{$this->photoId} failed in {$this->jobName}: {$this->message}",
            'url' => url('/admin'),
        ];
    }
}
