<?php

namespace Tests\Feature;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0 (phần 2) — Khi admin thêm phản hồi PUBLIC cho ticket, người tạo ticket
 * (USER) phải nhận thông báo (chuông + email). Ghi chú nội bộ (internal note)
 * và tự trả lời (chính người tạo reply) thì KHÔNG báo.
 *
 * Observer SupportTicketReplyObserver phát trên sự kiện Eloquent `created`.
 */
class TicketRepliedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCreator(): User
    {
        return User::factory()->create();
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    }

    public function test_public_reply_notifies_ticket_creator(): void
    {
        $creator = $this->makeCreator();
        $admin = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['user_id' => $creator->id]);

        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Chúng tôi đã tiếp nhận và đang xử lý.',
            'is_internal_note' => false,
        ]);

        // Đúng 1 event TICKET_REPLIED gắn với ticket.
        $this->assertDatabaseCount('notification_events', 1);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_TICKET_REPLIED)->firstOrFail();
        $this->assertSame(SupportTicket::class, $event->subject_type);
        $this->assertSame($ticket->id, $event->subject_id);

        // Đúng 1 recipient là người tạo ticket.
        $this->assertDatabaseCount('notification_recipients', 1);
        $recipient = NotificationRecipient::where('notification_event_id', $event->id)->firstOrFail();
        $this->assertSame($creator->id, $recipient->user_id);
    }

    public function test_internal_note_does_not_notify_user(): void
    {
        $creator = $this->makeCreator();
        $admin = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['user_id' => $creator->id]);

        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Note nội bộ: gọi lại khách hàng lúc 15h.',
            'is_internal_note' => true,
        ]);

        // Ghi chú nội bộ không sinh thông báo nào cho user.
        $this->assertDatabaseCount('notification_events', 0);
        $this->assertDatabaseCount('notification_recipients', 0);
    }

    public function test_creator_self_reply_does_not_self_notify(): void
    {
        $creator = $this->makeCreator();
        $ticket = SupportTicket::factory()->create(['user_id' => $creator->id]);

        // Trường hợp người tạo ticket tự thêm câu hỏi (nếu có luồng client reply).
        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $creator->id,
            'message' => 'Bổ sung: tôi gửi nhầm số điện thoại.',
            'is_internal_note' => false,
        ]);

        // Không tự báo cho chính mình.
        $this->assertDatabaseCount('notification_events', 0);
        $this->assertDatabaseCount('notification_recipients', 0);
    }
}
