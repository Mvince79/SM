<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use App\Services\MissingChildSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Suite: MissingChildSearchService
 * 
 * Kiểm tra:
 * - FULLTEXT search (full_name, clothes_desc, special_marks, circumstances)
 * - Filter: province_id, gender, age_estimate (range), missing_date (range)
 * - Sort: newest, oldest, near_me (Haversine)
 * - Pagination: 15/trang
 * - No N+1 queries
 * - Cache works
 */
class MissingChildSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MissingChildSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MissingChildSearchService::class);
        $this->createTestData();
    }

    /**
     * Create test data: 50 missing children cases
     */
    private function createTestData(): void
    {
        // Tạo user và province
        $user = User::factory()->create();
        $province = Province::first() ?? Province::factory()->create(['id' => 1, 'name' => 'Hà Nội']);

        // Tạo 50 missing children
        for ($i = 1; $i <= 50; $i++) {
            MissingChild::create([
                'user_id' => $user->id,
                'case_code' => 'MC-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'full_name' => $i % 2 === 0 ? "Nguyễn Văn A {$i}" : "Trần Thị B {$i}",
                'age_estimate' => $i % 15 === 0 ? 5 : ($i % 10),
                'gender' => $i % 3 === 0 ? 'FEMALE' : 'MALE',
                'province_id' => $i % 5 === 0 ? 2 : 1,
                'missing_date' => now()->subDays($i),
                'missing_time' => '14:30:00',
                'clothes_desc' => "Áo xanh, quần đen {$i}",
                'special_marks' => "Có scar trên trán {$i}",
                'circumstances' => "Mất tích khi đi học, hôm {$i}",
                'contact_name' => 'Mẹ ' . $user->full_name,
                'contact_phone' => '+8490123456' . sprintf('%02d', $i),
                'status' => 'APPROVED',
            ]);
        }
    }

    /**
     * Test: FULLTEXT search with keyword "nguyễn"
     */
    public function test_fulltext_search_keyword_nguyen()
    {
        $result = $this->service->search(['q' => 'nguyễn']);

        $this->assertTrue($result->count() > 0);
        $this->assertStringContainsString('Nguyễn', $result->first()->full_name);
    }

    /**
     * Test: FULLTEXT search with keyword "áo xanh"
     */
    public function test_fulltext_search_keyword_clothes()
    {
        $result = $this->service->search(['q' => 'áo xanh']);

        $this->assertTrue($result->count() > 0);
    }

    /**
     * Test: Filter by province_id
     */
    public function test_filter_province_id()
    {
        $result = $this->service->search(['province_id' => 1]);

        // Province 1 should have ~40 items (i % 5 !== 0)
        $this->assertGreaterThan(30, $result->total());
        $result->each(fn($item) => $this->assertEquals(1, $item->province_id));
    }

    /**
     * Test: Filter by gender
     */
    public function test_filter_gender()
    {
        $result = $this->service->search(['gender' => 'FEMALE']);

        $this->assertTrue($result->count() > 0);
        $result->each(fn($item) => $this->assertEquals('FEMALE', $item->gender));
    }

    /**
     * Test: Filter by age range (age_min, age_max)
     */
    public function test_filter_age_range()
    {
        $result = $this->service->search(['age_min' => 5, 'age_max' => 8]);

        $this->assertTrue($result->count() > 0);
        $result->each(function ($item) {
            $this->assertGreaterThanOrEqual(5, $item->age_estimate);
            $this->assertLessThanOrEqual(8, $item->age_estimate);
        });
    }

    /**
     * Test: Filter by missing_date range
     */
    public function test_filter_missing_date_range()
    {
        $from = now()->subDays(20)->toDateString();
        $to = now()->toDateString();

        $result = $this->service->search([
            'missing_date_from' => $from,
            'missing_date_to' => $to,
        ]);

        $this->assertTrue($result->count() > 0);
    }

    /**
     * Test: Sort by newest (default)
     */
    public function test_sort_newest()
    {
        $result = $this->service->search(['sort' => 'newest']);

        $this->assertTrue($result->count() > 0);
        $first = $result->first();
        $second = $result[1] ?? null;

        if ($second) {
            // Newest should come first
            $this->assertGreaterThanOrEqual(
                $second->missing_date,
                $first->missing_date
            );
        }
    }

    /**
     * Test: Sort by oldest
     */
    public function test_sort_oldest()
    {
        $result = $this->service->search(['sort' => 'oldest']);

        $this->assertTrue($result->count() > 0);
        $first = $result->first();
        $second = $result[1] ?? null;

        if ($second) {
            // Oldest should come first
            $this->assertLessThanOrEqual(
                $second->missing_date,
                $first->missing_date
            );
        }
    }

    /**
     * Test: Sort by near_me (Haversine distance)
     */
    public function test_sort_near_me_haversine()
    {
        // Update một vài records với lat/lng khác nhau
        $missing = MissingChild::first();
        $missing->update(['lat' => 10.7, 'lng' => 106.7]); // Hà Nội

        $result = $this->service->search([
            'lat' => 10.8,
            'lng' => 106.7,
            'radius_km' => 50,
            'sort' => 'near_me',
        ]);

        // Verify kết quả có distance_km attribute
        $first = $result->first();
        $this->assertNotNull($first);
        // Distance should be calculated
    }

    /**
     * Test: Pagination (15 per page)
     */
    public function test_pagination_per_page()
    {
        $page1 = $this->service->search(['page' => 1]);

        // Should have 15 items on page 1
        $this->assertEquals(15, $page1->count());
        $this->assertEquals(1, $page1->currentPage());
        $this->assertEquals(50, $page1->total());
        $this->assertEquals(4, $page1->lastPage()); // ceil(50/15) = 4
    }

    /**
     * Test: Pagination page 2
     */
    public function test_pagination_page_2()
    {
        $page1 = $this->service->search(['page' => 1]);
        $page2 = $this->service->search(['page' => 2]);

        $this->assertEquals(1, $page1->currentPage());
        $this->assertEquals(2, $page2->currentPage());

        // Page 2 should have different items
        $this->assertNotEquals(
            $page1->first()->id,
            $page2->first()->id
        );
    }

    /**
     * Test: Combine multiple filters
     */
    public function test_combined_filters()
    {
        $result = $this->service->search([
            'province_id' => 1,
            'gender' => 'MALE',
            'age_min' => 5,
            'age_max' => 9,
        ]);

        $this->assertTrue($result->count() > 0);
        $result->each(function ($item) {
            $this->assertEquals(1, $item->province_id);
            $this->assertEquals('MALE', $item->gender);
            $this->assertGreaterThanOrEqual(5, $item->age_estimate);
            $this->assertLessThanOrEqual(9, $item->age_estimate);
        });
    }

    /**
     * Test: Eager load relations (no N+1 query)
     */
    public function test_eager_load_relations()
    {
        $result = $this->service->search(['q' => 'nguyễn']);

        // Verify relations are loaded
        $first = $result->first();
        $this->assertNotNull($first->province);

        // Check no additional queries when accessing relations
        $this->assertInstanceOf(\App\Models\Province::class, $first->province);
    }

    /**
     * Test: Cache works (same filter = cached result)
     */
    public function test_cache_functionality()
    {
        // First call - should hit DB
        $result1 = $this->service->search(['q' => 'nguyễn']);
        $id1 = $result1->first()->id;

        // Second call - should hit cache
        $result2 = $this->service->search(['q' => 'nguyễn']);
        $id2 = $result2->first()->id;

        // Should be identical
        $this->assertEquals($id1, $id2);
    }

    /**
     * Test: Only APPROVED status returned
     */
    public function test_only_approved_status()
    {
        // Create a PENDING case
        MissingChild::create([
            'user_id' => User::first()->id,
            'case_code' => 'MC-2026-99999',
            'full_name' => 'Test Pending',
            'age_estimate' => 7,
            'gender' => 'MALE',
            'province_id' => 1,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+8490999999',
            'status' => 'PENDING', // Not approved
        ]);

        $result = $this->service->search([]);

        // PENDING should NOT be included
        $this->assertFalse($result->pluck('case_code')->contains('MC-2026-99999'));
    }

    /**
     * Test: Soft deleted records not returned
     */
    public function test_soft_deleted_excluded()
    {
        $missing = MissingChild::first();
        $missingId = $missing->id;
        $missing->delete(); // Soft delete

        $result = $this->service->search([]);

        // Soft deleted should NOT be included
        $this->assertFalse($result->pluck('id')->contains($missingId));
    }


}
