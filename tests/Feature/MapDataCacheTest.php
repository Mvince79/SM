<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test Suite: C-01 — Redis Cache cho Map Data
 *
 * phpunit.xml đặt CACHE_STORE=array khi chạy test (không cần Redis thật),
 * và ArrayStore của Laravel hỗ trợ Cache::tags() giống Redis nên hành vi
 * cache/invalidate được kiểm tra ở đây phản ánh đúng thực tế khi chạy
 * với CACHE_STORE=redis ở production.
 */
class MapDataCacheTest extends TestCase
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

    /** DoD #1: gọi 2 lần liên tiếp -> lần 2 phải là cache HIT (không đọc DB mới). */
    public function test_second_call_is_served_from_cache(): void
    {
        $this->makeMissingChild();

        $first = $this->getJson('/api/v1/map-data');
        $first->assertOk();
        $this->assertSame(1, $first->json('count'));

        // Thêm 1 bản ghi mới nhưng CHỐ QUA insert() thẳng (bypass Eloquent
        // events) để mô phỏng "có dữ liệu mới trong DB nhưng observer
        // không được gọi" — nếu controller thực sự cache đúng, response
        // lần 2 vẫn phải trả về count cũ (1), KHÔNG đọc lại DB.
        DB::table('missing_children')->insert([
            'user_id' => $this->user->id,
            'case_code' => 'MC-2026-99999',
            'full_name' => 'Bypass Observer',
            'age_estimate' => 9,
            'gender' => 'MALE',
            'province_id' => $this->province->id,
            'lat' => 16.06,
            'lng' => 108.21,
            'missing_date' => now()->subDay(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84900000000',
            'status' => 'APPROVED',
            'review_level' => 'STANDARD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $second = $this->getJson('/api/v1/map-data');
        $second->assertOk();
        $this->assertSame(
            1,
            $second->json('count'),
            'Lần gọi thứ 2 phải là cache HIT: vẫn thấy count=1 dù DB đã có 2 bản ghi, '
            .'vì bản ghi mới được thêm bằng insert() thẳng (không qua Observer nên không invalidate cache).'
        );
    }

    /** DoD #2: duyệt 1 tin mới -> gọi lại API map-data thấy tin mới ngay, không bị cache cũ che mất. */
    public function test_approving_a_case_invalidates_cache_immediately(): void
    {
        $pending = $this->makeMissingChild(['status' => 'PENDING']);

        // Warm cache trong lúc case còn PENDING -> map-data chưa thấy case này.
        $before = $this->getJson('/api/v1/map-data');
        $before->assertOk();
        $this->assertSame(0, $before->json('count'));

        // Duyệt tin (qua Eloquent -> MissingChildObserver::updated() chạy
        // -> tự invalidate cache map-data).
        $pending->update(['status' => 'APPROVED']);

        $after = $this->getJson('/api/v1/map-data');
        $after->assertOk();
        $this->assertSame(
            1,
            $after->json('count'),
            'Ngay sau khi duyệt tin, /api/v1/map-data phải thấy tin mới ngay lập tức, '
            .'không phải đợi cache cũ hết TTL.'
        );
    }

    /** Xoá mềm 1 case đã APPROVED cũng phải invalidate cache map-data. */
    public function test_deleting_a_case_invalidates_cache(): void
    {
        $child = $this->makeMissingChild();

        $before = $this->getJson('/api/v1/map-data');
        $this->assertSame(1, $before->json('count'));

        $child->delete();

        $after = $this->getJson('/api/v1/map-data');
        $this->assertSame(
            0,
            $after->json('count'),
            'Sau khi xoá (soft delete) case đang hiển thị, marker phải biến mất ngay khỏi map-data.'
        );
    }
}
