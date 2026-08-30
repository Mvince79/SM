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
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $case = MissingChild::factory()
            ->approved()
            ->create([
                'user_id' => $family->id,
                'status' => MissingChild::STATUS_APPROVED,
            ]);

        // Mô phỏng nút "Đánh dấu đã bàn giao" (ViewMissingChild / MissingChildrenTable):
        // admin mới là người thực hiện → observer gửi HANDOVER_READY cho gia đình
        // (chỉ gửi khi actor là ADMIN, theo thiết kế mới). Admin luôn nhập lý do
        // (audit_reason) — giống như Filament action set trước khi update.
        $this->actingAs($admin);
        $case->audit_reason = 'Đã xác minh qua công an phường, gia đình xác nhận đã nhận bé về.';
        $case->update([
            'status' => MissingChild::STATUS_HANDED_OVER,
            'handover_point_id' => null,
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => $admin->id,
        ]);

        // Đúng 1 NotificationEvent HANDOVER_READY gắn với hồ sơ này (không đếm các
        // sự kiện phát khi tạo hồ sơ như CASE_SUBMITTED gửi cho admin).
        $this->assertSame(1, NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_HANDOVER_READY)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->id)
            ->count());
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->id,
        ]);

        // Đúng 1 NotificationRecipient cho sự kiện HANDOVER_READY, và chính là
        // gia đình (chủ hồ sơ). CASE_SUBMITTED (gửi admin) không tính vào đây.
        $this->assertSame(1, NotificationRecipient::query()
            ->whereHas('event', fn ($q) => $q
                ->where('event_type', NotificationEvent::TYPE_HANDOVER_READY)
                ->where('subject_id', $case->id))
            ->where('user_id', $family->id)
            ->count());
        $recipient = NotificationRecipient::where('user_id', $family->id)->first();
        $this->assertNotNull($recipient);
        $this->assertSame(
            NotificationEvent::TYPE_HANDOVER_READY,
            $recipient->event->event_type
        );
        $this->assertSame($case->id, $recipient->event->subject_id);
    }
}
