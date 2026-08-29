<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoverReadyNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_handover_notifies_the_family_with_handover_ready_event(): void
    {
        $family = User::factory()->create(['role' => 'USER']);

        $case = MissingChild::factory()
            ->approved()
            ->create([
                'user_id' => $family->id,
                'status' => MissingChild::STATUS_APPROVED,
            ]);

        // Mô phỏng nút "Đánh dấu đã bàn giao" (ViewMissingChild / MissingChildrenTable):
        // chỉ đổi status sang HANDED_OVER, observer sẽ tự phát thông báo.
        $case->update([
            'status' => MissingChild::STATUS_HANDED_OVER,
            'handover_point_id' => null,
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => $family->id,
        ]);

        // Đúng 1 NotificationEvent HANDOVER_READY gắn với hồ sơ này.
        $this->assertDatabaseCount('notification_events', 1);
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->id,
        ]);

        // Đúng 1 NotificationRecipient, và chính là gia đình (chủ hồ sơ).
        $this->assertDatabaseCount('notification_recipients', 1);
        $recipient = NotificationRecipient::where('user_id', $family->id)->first();
        $this->assertNotNull($recipient);
        $this->assertSame(
            NotificationEvent::TYPE_HANDOVER_READY,
            $recipient->event->event_type
        );
        $this->assertSame($case->id, $recipient->event->subject_id);
    }
}
