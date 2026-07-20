<?php

namespace App\Jobs;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchProvinceCaseApprovedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $missingChildId)
    {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    public function handle(NotificationDeliveryService $deliveryService): void
    {
        $case = MissingChild::query()
            ->with('province')
            ->find($this->missingChildId);

        if (! $case || $case->status !== 'APPROVED' || ! $case->province_id) {
            return;
        }

        $event = NotificationEvent::query()->firstOrCreate([
            'event_type' => NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ], [
            'payload' => [
                'case_code' => $case->case_code,
                'province_id' => $case->province_id,
            ],
            'triggered_by_user_id' => $case->approved_by,
            'occurred_at' => $case->approved_at ?? now(),
        ]);
        $event->setRelation('subject', $case);

        $users = User::query()
            ->active()
            ->where('province_id', $case->province_id)
            ->whereIn('role', ['USER', 'VOLUNTEER'])
            ->get();

        $deliveryService->deliver($event, $users);
    }
}
