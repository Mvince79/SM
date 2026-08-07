<?php

namespace Tests\Feature;

use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D-01 — Feature Test: Trung tâm thông báo / phân quyền xem thông báo theo user.
 * Viết khớp trực tiếp theo NotificationController thật.
 *
 * GHI CHÚ: show()/read() dùng $request->user()->notificationRecipients()->whereKey($id)
 * (đã scope theo user hiện tại) rồi firstOrFail() — nghĩa là cố mở thông báo của
 * người khác sẽ ra 404 (Model Not Found), KHÔNG phải 403, vì query không bao giờ
 * tìm thấy bản ghi thuộc về user khác trong quan hệ của mình.
 */
class NotificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_notification_routes(): void
    {
        $notification = NotificationRecipient::factory()->create();

        $this->get(route('notifications.center'))->assertRedirect();
        $this->get(route('notifications.dropdown'))->assertRedirect();
        $this->get(route('notifications.show', ['notification' => $notification->id]))->assertRedirect();
        $this->patch(route('notifications.read', ['notification' => $notification->id]))->assertRedirect();
    }

    public function test_dropdown_returns_only_current_user_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        NotificationRecipient::factory()->count(2)->create(['user_id' => $user->id]);
        NotificationRecipient::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson(route('notifications.dropdown'));

        $response->assertOk()
            ->assertJsonCount(2, 'notifications');
    }

    public function test_dropdown_unread_count_is_accurate(): void
    {
        $user = User::factory()->create();

        NotificationRecipient::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);
        NotificationRecipient::factory()->read()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('notifications.dropdown'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_dropdown_does_not_expose_more_than_ten_notifications(): void
    {
        $user = User::factory()->create();
        NotificationRecipient::factory()->count(15)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson(route('notifications.dropdown'));

        $response->assertOk()
            ->assertJsonCount(10, 'notifications');
    }

    public function test_user_can_view_own_notification_detail(): void
    {
        $user = User::factory()->create();
        $notification = NotificationRecipient::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('notifications.show', ['notification' => $notification->id]));

        $response->assertOk();
        $response->assertViewIs('notifications.show');
    }

    public function test_user_cannot_view_another_users_notification_detail(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = NotificationRecipient::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->get(route('notifications.show', ['notification' => $notification->id]));

        $response->assertStatus(404);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = NotificationRecipient::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('notifications.read', ['notification' => $notification->id]));

        $response->assertOk()
            ->assertJson(['read' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = NotificationRecipient::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('notifications.read', ['notification' => $notification->id]));

        $response->assertStatus(404);
        $this->assertNull($notification->fresh()->read_at);
    }
}