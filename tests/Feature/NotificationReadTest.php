<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [
                'title' => 'Test',
                'message' => 'Message',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('notifications.read', ['notification' => $notification->id]));

        $response
            ->assertOk()
            ->assertJson([
                'read' => true,
                'unread_count' => 0,
            ]);

        $this->assertNotNull($notification->refresh()->read_at);
    }
}
