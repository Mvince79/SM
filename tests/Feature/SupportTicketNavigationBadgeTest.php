<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * P0 — SupportTicketResource phải có badge đếm ticket đang mở (OPEN/IN_PROGRESS)
 * trên sidebar admin (copy pattern Item 10: FoundChildNavigationBadgeTest).
 */
class SupportTicketNavigationBadgeTest extends TestCase
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

    private function makeOpenTicket(): SupportTicket
    {
        return SupportTicket::factory()->create(['status' => 'OPEN']);
    }

    public function test_support_ticket_nav_badge_shows_open_count_on_admin_sidebar(): void
    {
        Cache::forget('badge_support_tickets_open');

        // 3 ticket đang mở -> badge phải hiện số 3
        $this->makeOpenTicket();
        $this->makeOpenTicket();
        $this->makeOpenTicket();

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();

        // Nhãn menu (navigation label của SupportTicketResource)
        $response->assertSee('Yêu Cầu Hỗ Trợ');

        // Filament render badge trong container fi-sidebar-item-badge-ctn
        $response->assertSee('fi-sidebar-item-badge-ctn');

        // Số đếm thực tế trên badge phải bằng số ticket đang mở thật trong DB
        $expected = (string) SupportTicket::open()->count();
        $this->assertSame('3', $expected);
        $response->assertSee('3');

        // Resource method cũng trả về đúng số
        Cache::forget('badge_support_tickets_open');
        $this->assertSame('3', SupportTicketResource::getNavigationBadge());
    }

    public function test_support_ticket_nav_badge_is_hidden_when_no_open_ticket(): void
    {
        Cache::forget('badge_support_tickets_open');

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Yêu Cầu Hỗ Trợ');

        // Không có ticket đang mở -> badge method trả về null
        Cache::forget('badge_support_tickets_open');
        $this->assertNull(SupportTicketResource::getNavigationBadge());

        $this->assertSame(
            0,
            (int) SupportTicket::open()->count()
        );
    }
}
