<?php

namespace Tests\Feature;

use App\Models\EscalationQueue;
use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use Database\Seeders\DemoEscalationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoEscalationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_an_unread_confirmed_match_for_the_emergency_dashboard(): void
    {
        $this->seed(DemoEscalationSeeder::class);

        $event = NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_FACE_MATCH_CONFIRMED)
            ->sole();
        $recipient = NotificationRecipient::query()
            ->with(['user', 'event.subject'])
            ->sole();
        $escalation = EscalationQueue::query()
            ->with('notificationRecipient.user')
            ->sole();

        $this->assertSame('CONFIRMED', $event->subject->status);
        $this->assertNull($recipient->read_at);
        $this->assertSame(DemoEscalationSeeder::FAMILY_EMAIL, $recipient->user->email);
        $this->assertSame('0905123456', $recipient->user->phone);
        $this->assertSame(EscalationQueue::STATUS_PENDING, $escalation->status);
        $this->assertSame($recipient->getKey(), $escalation->notification_recipient_id);
        $this->assertSame(
            'PARENT',
            MissingChild::query()
                ->where('case_code', DemoEscalationSeeder::CASE_CODE)
                ->value('contact_relation'),
        );
        $this->assertSame(
            DemoEscalationSeeder::CASE_SLUG,
            MissingChild::query()
                ->where('case_code', DemoEscalationSeeder::CASE_CODE)
                ->value('slug'),
        );
        $this->assertTrue(
            $event->occurred_at->lte(
                now()->subMinutes(
                    (int) config('notification.escalation_threshold_minutes')
                )
            )
        );
    }

    public function test_it_can_be_run_repeatedly_without_duplicating_demo_records(): void
    {
        $this->seed(DemoEscalationSeeder::class);
        $recipient = NotificationRecipient::query()->sole();
        $recipient->update(['read_at' => now()]);
        $recipient->escalationQueue()->update([
            'status' => EscalationQueue::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $this->seed(DemoEscalationSeeder::class);

        $this->assertSame(1, FaceMatchResult::query()->count());
        $this->assertSame(1, NotificationEvent::query()->count());
        $this->assertSame(1, NotificationRecipient::query()->count());
        $this->assertSame(1, EscalationQueue::query()->count());
        $this->assertNull($recipient->fresh()->read_at);
        $this->assertSame(
            EscalationQueue::STATUS_PENDING,
            EscalationQueue::query()->sole()->status,
        );
    }
}
