<?php

namespace Tests\Feature;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1 (phần cuối module Ticket) — Khi admin đánh dấu ticket "đã xử lý"
 * (RESOLVED) hoặc "đóng" (CLOSED), người tạo ticket (USER) phải nhận thông báo.
 *
 * SupportTicketObserver phát trên sự kiện Eloquent `updated` khi wasChanged('status').
 */
class TicketStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCreator(): User
    {
        return User::factory()->create();
    }

    public function test_resolved_status_notifies_ticket_creator(): void
    {
        $creator = $this->makeCreator();
        $ticket = SupportTicket::factory()->create([
            'user_id' => $creator->id,
            'status' => 'OPEN',
        ]);

        $ticket->update(['status' => 'RESOLVED']);

        $this->assertDatabaseCount('notification_events', 1);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_TICKET_RESOLVED)->firstOrFail();
        $this->assertSame(SupportTicket::class, $event->subject_type);
        $this->assertSame($ticket->id, $event->subject_id);

        $this->assertDatabaseCount('notification_recipients', 1);
        $recipient = NotificationRecipient::where('notification_event_id', $event->id)->firstOrFail();
        $this->assertSame($creator->id, $recipient->user_id);
    }

    public function test_closed_status_notifies_ticket_creator(): void
    {
        $creator = $this->makeCreator();
        $ticket = SupportTicket::factory()->create([
            'user_id' => $creator->id,
            'status' => 'IN_PROGRESS',
        ]);

        $ticket->update(['status' => 'CLOSED']);

        $this->assertDatabaseCount('notification_events', 1);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_TICKET_CLOSED)->firstOrFail();
        $this->assertSame(SupportTicket::class, $event->subject_type);
        $this->assertSame($ticket->id, $event->subject_id);

        $this->assertDatabaseCount('notification_recipients', 1);
        $recipient = NotificationRecipient::where('notification_event_id', $event->id)->firstOrFail();
        $this->assertSame($creator->id, $recipient->user_id);
    }

    public function test_in_progress_status_does_not_notify_user(): void
    {
        $creator = $this->makeCreator();
        $ticket = SupportTicket::factory()->create([
            'user_id' => $creator->id,
            'status' => 'OPEN',
        ]);

        // Chuyển sang IN_PROGRESS (admin nhận xử lý) — không báo user.
        $ticket->update(['status' => 'IN_PROGRESS']);

        $this->assertDatabaseCount('notification_events', 0);
        $this->assertDatabaseCount('notification_recipients', 0);
    }
}
