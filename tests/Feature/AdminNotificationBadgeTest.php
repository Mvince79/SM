<?php

namespace Tests\Feature;

use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_topbar_shows_unread_count_badge(): void
    {
        $admin = $this->makeAdmin();

        // 4 thông báo chưa đọc dành cho admin
        NotificationRecipient::factory()->count(4)->create([
            'user_id' => $admin->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();

        // Phần tử đặc trưng của badge admin (Step 4: tĩnh, không JS)
        $response->assertSee('data-admin-notification-badge');
        $response->assertSee('data-admin-notification-count');

        // Số đếm phải khớp số chưa đọc thật trong DB
        $expected = (int) $admin->notificationRecipients()->whereNull('read_at')->count();
        $this->assertSame(4, $expected);

        $response->assertSee('data-admin-notification-count>4</span>', false);
    }

    public function test_admin_topbar_hides_badge_when_no_unread(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('data-admin-notification-badge');
        $this->assertSame(
            0,
            (int) $admin->notificationRecipients()->whereNull('read_at')->count()
        );
    }
}
