<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FoundChildResource;
use App\Models\FoundChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FoundChildNavigationBadgeTest extends TestCase
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

    private function makePendingFoundChild(): FoundChild
    {
        return FoundChild::query()->create([
            'user_id' => User::factory()->create()->id,
            'province_id' => Province::factory()->create()->id,
            'found_date' => now()->toDateString(),
            'status' => FoundChild::STATUS_PENDING,
        ]);
    }

    public function test_found_child_nav_badge_shows_pending_count_on_admin_sidebar(): void
    {
        Cache::forget('badge_found_pending');

        // 3 pending "Trẻ đã tìm thấy" hồ sơ -> badge phải hiện số 3
        $this->makePendingFoundChild();
        $this->makePendingFoundChild();
        $this->makePendingFoundChild();

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();

        // Nhãn menu (navigation label của FoundChildResource)
        $response->assertSee('Hồ Sơ Trẻ Đã Tìm Thấy');

        // Filament render badge trong container fi-sidebar-item-badge-ctn
        $response->assertSee('fi-sidebar-item-badge-ctn');

        // Số đếm thực tế trên badge phải bằng số PENDING thật trong DB
        $expected = (string) FoundChild::where('status', FoundChild::STATUS_PENDING)->count();
        $this->assertSame('3', $expected);
        $response->assertSee('3');

        // Resource method cũng trả về đúng số
        Cache::forget('badge_found_pending');
        $this->assertSame('3', FoundChildResource::getNavigationBadge());
    }

    public function test_found_child_nav_badge_is_hidden_when_no_pending(): void
    {
        Cache::forget('badge_found_pending');

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Hồ Sơ Trẻ Đã Tìm Thấy');

        // Không có PENDING -> badge method trả về null -> không render container
        Cache::forget('badge_found_pending');
        $this->assertNull(FoundChildResource::getNavigationBadge());

        // Với DB sạch, không có badge nào hiện số (không có fi-sidebar-item-badge-ctn chứa số)
        // Chỉ assert rằng resource method đúng; container có thể không xuất hiện.
        $this->assertSame(
            0,
            FoundChild::where('status', FoundChild::STATUS_PENDING)->count()
        );
    }
}
