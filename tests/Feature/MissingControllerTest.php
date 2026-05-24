<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Suite: MissingController (Web Search)
 * 
 * Kiểm tra:
 * - GET /missing -> danh sách với search
 * - Query params: q, province_id, gender, age_min, age_max, sort, page
 * - Pagination hoạt động
 * - Filter pills trạng thái
 */
class MissingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedData();
    }

    private function seedData(): void
    {
        $user = User::factory()->create();
        $province1 = Province::first() ?? Province::factory()->create(['id' => 1, 'name' => 'Hà Nội']);
        $province2 = Province::factory()->create(['id' => 2, 'name' => 'Hồ Chí Minh', 'short_code' => 'HCM']);

        // Create 20 test cases
        for ($i = 1; $i <= 20; $i++) {
            MissingChild::create([
                'user_id' => $user->id,
                'case_code' => 'MC-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'full_name' => $i % 2 === 0 ? "Nguyễn Văn A {$i}" : "Trần Thị B {$i}",
                'age_estimate' => 5 + ($i % 10),
                'gender' => $i % 3 === 0 ? 'FEMALE' : 'MALE',
                'province_id' => $i % 3 === 0 ? 2 : 1,
                'missing_date' => now()->subDays($i),
                'clothes_desc' => "Áo xanh, quần đen {$i}",
                'special_marks' => "Scar {$i}",
                'circumstances' => "Mất tích hôm {$i}",
                'contact_name' => 'Parent',
                'contact_phone' => "+8490123456{$i}",
                'status' => 'APPROVED',
            ]);
        }
    }

    /**
     * Test: GET /missing returns list view with pagination
     */
    public function test_get_missing_index_returns_list()
    {
        $response = $this->get('/missing');

        $response->assertStatus(200);
        $response->assertViewIs('missing.index');
        $response->assertViewHas('cases');
        $response->assertViewHas('provinces');
        $response->assertViewHas('filters');
    }

    /**
     * Test: GET /missing?q=nguyen - FULLTEXT search
     */
    public function test_search_with_keyword()
    {
        $response = $this->get('/missing?q=Nguyễn');

        $response->assertStatus(200);
        $response->assertViewHas('cases');
        
        $cases = $response['cases'];
        $this->assertTrue(count($cases) > 0);
    }

    /**
     * Test: GET /missing?province_id=1 - filter by province
     */
    public function test_filter_by_province()
    {
        $response = $this->get('/missing?province_id=1');

        $response->assertStatus(200);
        
        $cases = $response['cases'];
        $this->assertTrue(count($cases) > 0);
    }

    /**
     * Test: GET /missing?gender=MALE - filter by gender
     */
    public function test_filter_by_gender()
    {
        $response = $this->get('/missing?gender=MALE');

        $response->assertStatus(200);
        
        $cases = $response['cases'];
        $this->assertTrue(count($cases) > 0);
    }

    /**
     * Test: GET /missing?age_min=5&age_max=10 - filter by age range
     */
    public function test_filter_by_age_range()
    {
        $response = $this->get('/missing?age_min=5&age_max=10');

        $response->assertStatus(200);
        
        $cases = $response['cases'];
        $this->assertTrue(count($cases) > 0);
    }

    /**
     * Test: GET /missing?sort=oldest - sort by oldest
     */
    public function test_sort_by_oldest()
    {
        $response = $this->get('/missing?sort=oldest');

        $response->assertStatus(200);
        
        $cases = $response['cases'];
        $this->assertTrue(count($cases) > 0);
    }

    /**
     * Test: GET /missing?page=2 - pagination
     */
    public function test_pagination_page_2()
    {
        $response = $this->get('/missing?page=2');

        $response->assertStatus(200);
        $response->assertViewHas('cases');
    }

    /**
     * Test: GET /missing - combined filters
     */
    public function test_combined_filters()
    {
        $response = $this->get('/missing?province_id=1&gender=MALE&age_min=5&age_max=10');

        $response->assertStatus(200);
        $response->assertViewHas('cases');
    }

    /**
     * Test: GET /missing/detail?case_code=MC-2026-00001
     */
    public function test_get_detail_by_case_code()
    {
        $missing = MissingChild::first();
        
        $response = $this->get('/missing/detail?case_code=' . $missing->case_code);

        $response->assertStatus(200);
        $response->assertViewIs('missing.detail');
        $response->assertViewHas('case');
    }

    /**
     * Test: GET /missing/detail?id=1
     */
    public function test_get_detail_by_id()
    {
        $missing = MissingChild::first();
        
        $response = $this->get('/missing/detail?id=' . $missing->id);

        $response->assertStatus(200);
        $response->assertViewIs('missing.detail');
        $response->assertViewHas('case');
    }

    /**
     * Test: Non-APPROVED case not accessible (if logged out)
     */
    public function test_non_approved_case_not_accessible()
    {
        $user = User::first();
        $pending = MissingChild::create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-PENDING',
            'full_name' => 'Pending Case',
            'age_estimate' => 7,
            'gender' => 'MALE',
            'province_id' => 1,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => 'PENDING',
        ]);

        $response = $this->get('/missing/detail?case_code=' . $pending->case_code);

        $response->assertStatus(403);
    }

    /**
     * Test: Filters are passed to view
     */
    public function test_filters_passed_to_view()
    {
        $response = $this->get('/missing?q=test&province_id=1&gender=MALE&sort=oldest');

        $response->assertStatus(200);
        $filters = $response['filters'];
        
        $this->assertEquals('test', $filters['q']);
        $this->assertEquals(1, $filters['province_id']);
        $this->assertEquals('MALE', $filters['gender']);
        $this->assertEquals('oldest', $filters['sort']);
    }
}
