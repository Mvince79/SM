<?php

namespace Tests\Feature;

use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalationAutoResolveOnReunionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_escalation_auto_resolves_when_case_reunited(): void
    {
        $family = User::factory()->create(['role' => 'USER']);
        $case = MissingChild::factory()
            ->approved()
            ->create([
                'user_id' => $family->id,
                'status' => MissingChild::STATUS_APPROVED,
            ]);
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);

        // Mô phỏng đã từng leo thang lên admin: 1 EscalationQueue PENDING được
        // tạo bởi EscalateUnconfirmedReunionJob, gắn với hồ sơ qua
        // notification_recipient -> notification_events(subject = case).
        $event = NotificationEvent::create([
            'event_type' => NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->id,
            'payload' => [],
            'occurred_at' => now(),
        ]);
        $recipient = NotificationRecipient::create([
            'notification_event_id' => $event->id,
            'user_id' => $admin->id,
            'role_at_time' => 'ADMIN',
            'rendered_title' => 'Cần hỗ trợ xác nhận đoàn tụ',
            'rendered_body' => 'Gia đình chưa xác nhận đoàn tụ',
        ]);
        $escalation = EscalationQueue::create([
            'notification_recipient_id' => $recipient->id,
            'due_at' => now()->addDays(7),
            'status' => EscalationQueue::STATUS_PENDING,
        ]);

        // Chuyển hồ sơ sang REUNITED — observer phải tự đóng escalation.
        $case->update(['status' => MissingChild::STATUS_REUNITED]);

        $this->assertDatabaseHas('escalation_queue', [
            'id' => $escalation->id,
            'status' => EscalationQueue::STATUS_RESOLVED,
        ]);
        $this->assertNotNull($escalation->fresh()->resolved_at);
    }
}
