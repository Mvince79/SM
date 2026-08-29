<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReunionConfirmedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reunited_case_notifies_only_active_admins(): void
    {
        $family = User::factory()->create(['role' => 'USER']);

        $case = MissingChild::factory()
            ->approved()
            ->create([
                'user_id' => $family->id,
                'status' => MissingChild::STATUS_APPROVED,
            ]);

        // 2 admin active (1 ADMIN + 1 MODERATOR) — phải nhận thông báo.
        $adminActive = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
        $modActive = User::factory()->create(['role' => 'MODERATOR', 'status' => 'ACTIVE']);
        // 1 admin bị khoá — phải BỊ LOẠI TRỪ.
        $adminBanned = User::factory()->create(['role' => 'ADMIN', 'status' => 'BANNED']);

        // Mô phỏng chuyển sang REUNITED (gia đình/Admin xác nhận đoàn tụ).
        $case->update([
            'status' => MissingChild::STATUS_REUNITED,
        ]);

        // Đúng 1 NotificationEvent REUNION_CONFIRMED gắn với hồ sơ này.
        $this->assertDatabaseCount('notification_events', 1);
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_REUNION_CONFIRMED,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->id,
        ]);

        // Đúng số recipient = số admin active (2), KHÔNG gồm admin bị khoá,
        // và KHÔNG gồm gia đình (USER).
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_REUNION_CONFIRMED)->firstOrFail();
        $recipientUserIds = NotificationRecipient::where('notification_event_id', $event->id)
            ->pluck('user_id')
            ->all();

        $this->assertCount(2, $recipientUserIds);
        $this->assertContains($adminActive->id, $recipientUserIds);
        $this->assertContains($modActive->id, $recipientUserIds);
        $this->assertNotContains($adminBanned->id, $recipientUserIds);
        $this->assertNotContains($family->id, $recipientUserIds);
    }
}
