<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: trang "Cài đặt hệ thống" phải render được với quyền admin và
 * chứa các nhãn chính (đảm bảo UI không nổ runtime).
 */
class SystemSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $response = $this->actingAs($admin)->get('/admin/system-settings');

        $response->assertOk();
        $response->assertSee('Cài đặt hệ thống');
        $response->assertSee('Nhắc nhở & thông báo');
        $response->assertSee('Tham số AI so khớp');
        $response->assertSee('Ngưỡng khớp (match)');
        $response->assertSee('Đơn vị thời gian áp dụng');
    }

    public function test_guest_is_redirected(): void
    {
        $response = $this->get('/admin/system-settings');

        $response->assertRedirect();
    }
}
