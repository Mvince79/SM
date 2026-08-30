<?php

namespace Tests\Feature;

use App\Models\HandoverPoint;
use App\Repositories\HandoverPointRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bước 3 — Test HandoverPointRepository::nearestTo() với SQL Haversine (MySQL).
 *
 * SQLite trong môi trường build thiếu hàm toán học (sin/cos/atan2/radians/sqrt/acos)
 * nên SQL Haversine KHÔNG chạy được → test skip rõ ràng trên SQLite, dành chạy thật
 * trên MySQL. Công thức toán học đúng đã được verify ở tests/Unit/HaversineTest.php
 * (chạy được trên SQLite).
 */
class HandoverPointRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private HandoverPointRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(HandoverPointRepository::class);
    }

    /**
     * nearestTo() phải trả các điểm theo thứ tự khoảng cách tăng dần (gần → xa).
     * CHỈ chạy trên MySQL — SQLite thiếu hàm toán học.
     */
    public function test_nearest_to_orders_by_real_distance_mysql_only(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped(
                'SQL Haversine yêu cầu MySQL (SQLite thiếu hàm toán học). '
                    . 'Chạy trên MySQL để xác nhận thứ tự khoảng cách.'
            );
        }

        $close = HandoverPoint::factory()->create([
            'lat' => 10.775, 'lng' => 106.665,   // ~0.6 km từ (10.77, 106.66)
            'priority_order' => 0, 'is_active' => 1,
        ]);
        $medium = HandoverPoint::factory()->create([
            'lat' => 10.85, 'lng' => 106.70,      // ~9-10 km
            'priority_order' => 0, 'is_active' => 1,
        ]);
        $far = HandoverPoint::factory()->create([
            'lat' => 21.0278, 'lng' => 105.8342,  // Hà Nội ~1130 km
            'priority_order' => 0, 'is_active' => 1,
        ]);

        $nearest = $this->repository->nearestTo(10.77, 106.66, 3);

        $this->assertCount(3, $nearest);
        // Gần nhất phải đứng đầu, xa nhất phải đứng cuối
        $this->assertSame($close->id, $nearest->first()->id);
        $this->assertSame($far->id, $nearest->last()->id);

        // Thứ tự khoảng cách phải tăng dần
        $distances = $nearest->pluck('distance')->all();
        $this->assertLessThanOrEqual($distances[1], $distances[0]);
        $this->assertLessThanOrEqual($distances[2], $distances[1]);
    }
}
