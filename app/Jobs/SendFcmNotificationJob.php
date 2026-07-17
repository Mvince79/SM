<?php

namespace App\Jobs;

use App\Models\NotificationRecipient;
use App\Services\Notification\Contracts\FcmPushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $notificationRecipientId)
    {
        $this->onQueue('notifications');
    }

    public function handle(FcmPushSender $sender): void
    {
        $recipient = NotificationRecipient::query()
            ->with('user')
            ->find($this->notificationRecipientId);

        if (! $recipient?->user) {
            return;
        }

        $sender->send($recipient->user, [
            'title' => $recipient->rendered_title,
            'body' => $recipient->rendered_body,
            'action_url' => $recipient->action_url,
        ]);
    }
}
