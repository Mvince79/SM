<?php

namespace App\Jobs;

use App\Models\FaceMatchResult;
use App\Models\NotificationEvent;
use App\Services\Notification\ConfirmedMatchRecipientFinder;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchMatchConfirmedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $faceMatchResultId)
    {
        $this->onQueue('notifications');
    }

    public function handle(
        ConfirmedMatchRecipientFinder $recipientFinder,
        NotificationDeliveryService $deliveryService,
    ): void {
        $match = FaceMatchResult::query()
            ->with(['missingChild.user', 'foundChild.user'])
            ->find($this->faceMatchResultId);

        if (! $match || $match->status !== 'CONFIRMED') {
            return;
        }

        $event = NotificationEvent::query()->firstOrCreate([
            'event_type' => NotificationEvent::TYPE_FACE_MATCH_CONFIRMED,
            'subject_type' => $match->getMorphClass(),
            'subject_id' => $match->getKey(),
        ], [
            'payload' => [
                'missing_id' => $match->missing_id,
                'found_id' => $match->found_id,
                'score' => (float) $match->score,
            ],
            'triggered_by_user_id' => $match->confirmed_by,
            'occurred_at' => $match->confirmed_at ?? now(),
        ]);
        $event->setRelation('subject', $match);

        $deliveryService->deliver($event, $recipientFinder->find($match));
    }
}
