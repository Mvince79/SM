<?php

namespace Tests\Feature;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0 — Tạo ticket mới phải báo cho admin/moderator đang active qua đường ống
 * thông báo hợp nhất (chuông + email), và KHÔNG báo người tạo ticket.
 *
 * Gọi route thật (HTTP) vì đây là hành động public-facing quan trọng, không
 * chỉ tinker.
 */
class TicketCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_ticket_notifies_only_active_admins_and_mods(): void
    {
        // Người tạo ticket (USER) — không được nhận thông báo.
        $creator = User::factory()->create();

        // 2 admin + 1 moderator đang active → nhận thông báo.
        $admin1 = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $admin2 = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $mod = User::factory()->create(['role' => 'MODERATOR', 'status' => 'ACTIVE']);

        // 1 admin không active → KHÔNG nhận.
        $inactiveAdmin = User::factory()->create(['role' => 'ADMIN', 'status' => 'BANNED']);

        $this->actingAs($creator)
            ->post(route('support.store'), [
                'category' => 'BUG',
                'subject' => 'Không vào được trang cá nhân',
                'description' => 'Khi bấm vào avatar thì bị lỗi 500.',
                'priority' => 'HIGH',
            ])
            ->assertRedirect(route('support.index'));

        // Ticket được tạo với status mặc định OPEN.
        $ticket = SupportTicket::where('user_id', $creator->id)->latest()->first();
        $this->assertNotNull($ticket);
        $this->assertSame('OPEN', $ticket->status);

        // Đúng 1 event TICKET_CREATED, gắn với đúng ticket.
        $this->assertDatabaseCount('notification_events', 1);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_TICKET_CREATED)->firstOrFail();
        $this->assertSame(SupportTicket::class, $event->subject_type);
        $this->assertSame($ticket->id, $event->subject_id);

        // Đúng số recipient = active admin + moderator (3); loại creator & inactive.
        $this->assertDatabaseCount('notification_recipients', 3);

        $recipientUserIds = NotificationRecipient::where('notification_event_id', $event->id)
            ->pluck('user_id')
            ->all();

        $this->assertEqualsCanonicalizing([$admin1->id, $admin2->id, $mod->id], $recipientUserIds);
        $this->assertNotContains($creator->id, $recipientUserIds);
        $this->assertNotContains($inactiveAdmin->id, $recipientUserIds);
    }

    public function test_creating_ticket_creates_event_but_no_recipients_when_no_active_staff(): void
    {
        // Chỉ có admin không active → không ai nhận, nhưng ticket vẫn tạo thành công
        // và event vẫn được ghi (Notifier::send dùng firstOrCreate, idempotent).
        User::factory()->create(['role' => 'ADMIN', 'status' => 'BANNED']);
        $creator = User::factory()->create();

        $this->actingAs($creator)
            ->post(route('support.store'), [
                'category' => 'FEEDBACK',
                'subject' => 'Góp ý cải thiện',
                'description' => 'Nên thêm chế độ tối.',
            ])
            ->assertRedirect(route('support.index'));

        $this->assertDatabaseHas('support_tickets', ['user_id' => $creator->id]);
        // Event vẫn được tạo (1 row, gắn với ticket) nhưng không có recipient nào.
        $this->assertDatabaseCount('notification_events', 1);
        $this->assertDatabaseCount('notification_recipients', 0);
    }
}
