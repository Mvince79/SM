<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Suite: Ẩn hồ sơ đã giải quyết (REUNITED/CLOSED/REJECTED) khỏi heatmap & map-data.
 *
 * Mục tiêu: hồ sơ trẻ mất tích đã đoàn tụ/đóng/từ chối KHÔNG được vẽ điểm nhiệt
 * hay marker bản đồ, và phải biến mất NGAY khi chuyển sang trạng thái đó (observer
 * bump CacheVersion::MAP_DATA -> heatmap dùng chung version nên tự làm mới).
 *
 * phpunit.xml đặt CACHE_STORE=array khi chạy test (ArrayStore hỗ trợ cache như Redis).
 */
class HeatmapControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Province $province;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->province = Province::first()
            ?? Province::factory()->create(['id' => 1, 'name' => 'Đà Nẵng']);
    }

    private function makeMissingChild(array $overrides = []): MissingChild
    {
        return MissingChild::create(array_merge([
            'user_id' => $this->user->id,
            'case_code' => 'MC-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'full_name' => 'Nguyễn Văn Test',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $this->province->id,
            'lat' => 16.0544,
            'lng' => 108.2022,
            'missing_date' => now()->subDay(),
            'contact_name' => 'Parent Test',
            'contact_phone' => '+84901234567',
            'status' => 'APPROVED',
        ], $overrides));
    }

    /** Hồ sơ REUNITED (phụ huynh xác nhận đã về nhà) KHÔNG xuất hiện trên heatmap. */
    public function test_reunited_case_is_excluded_from_heatmap(): void
    {
        $this->makeMissingChild(['status' => MissingChild::STATUS_REUNITED]);

        $this->getJson('/map/heatmap-data')
            ->assertOk()
            ->assertJsonPath('meta.count', 0);
    }

    /** Hồ sơ CLOSED / REJECTED cũng bị ẩn khỏi heatmap. */
    public function test_closed_and_rejected_cases_are_excluded_from_heatmap(): void
    {
        $this->makeMissingChild(['status' => MissingChild::STATUS_CLOSED]);
        $this->makeMissingChild(['status' => MissingChild::STATUS_REJECTED]);

        $this->getJson('/map/heatmap-data')
            ->assertOk()
            ->assertJsonPath('meta.count', 0);
    }

    /** Hồ sơ APPROVED (đang tìm kiếm) và FOUND (đã tìm thấy) vẫn hiện trên heatmap. */
    public function test_active_cases_appear_on_heatmap(): void
    {
        $this->makeMissingChild(['status' => MissingChild::STATUS_APPROVED]);
        $this->makeMissingChild(['status' => MissingChild::STATUS_FOUND, 'found_at' => now()]);

        $this->getJson('/map/heatmap-data')
            ->assertOk()
            ->assertJsonPath('meta.count', 2);
    }

    /** Chuyển APPROVED -> REUNITED phải làm heatmap tự làm mới (ẩn ngay, không đợi TTL). */
    public function test_reunion_invalidates_heatmap_cache_immediately(): void
    {
        $child = $this->makeMissingChild(['status' => MissingChild::STATUS_APPROVED]);

        $this->getJson('/map/heatmap-data')->assertJsonPath('meta.count', 1);

        // Chuyển sang REUNITED qua Eloquent -> MissingChildObserver::updated() bump
        // CacheVersion::MAP_DATA -> heatmap (dùng chung version) sinh cache key mới.
        $child->update(['status' => MissingChild::STATUS_REUNITED]);

        $this->getJson('/map/heatmap-data')
            ->assertOk()
            ->assertJsonPath('meta.count', 0,
                'Ngay sau khi xác nhận đoàn tụ, điểm nhiệt phải biến mất khỏi heatmap.');
    }

    /** Cùng quy tắc áp dụng cho map-data (API bản đồ). */
    public function test_resolved_case_is_excluded_from_map_data(): void
    {
        $this->makeMissingChild(['status' => MissingChild::STATUS_REUNITED]);

        $this->getJson('/api/v1/map-data')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }
}
