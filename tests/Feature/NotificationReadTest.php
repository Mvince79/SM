<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $event = NotificationEvent::create([
            'event_type' => NotificationEvent::TYPE_CASE_APPROVED,
            'subject_type' => MissingChild::class,
            'subject_id' => 1,
            'payload' => ['case_code' => 'MC-000001'],
            'occurred_at' => now(),
        ]);

        $recipient = NotificationRecipient::create([
            'notification_event_id' => $event->getKey(),
            'user_id' => $user->getKey(),
            'role_at_time' => $user->role,
            'rendered_title' => 'Hồ sơ đã được duyệt',
            'rendered_body' => 'Thông báo kiểm thử',
            'action_url' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('notifications.read', ['notification' => $recipient->getKey()]));

        $response
            ->assertOk()
            ->assertJson([
                'read' => true,
                'unread_count' => 0,
            ]);

        $this->assertNotNull($recipient->refresh()->read_at);
    }
}
