<?php

namespace Tests\Unit;

use App\Support\Haversine;
use PHPUnit\Framework\TestCase;

/**
 * Bước 3 — Test thuần PHP cho công thức Haversine (chạy được trên SQLite).
 *
 * SQLite trong môi trường build này thiếu mọi hàm toán học (sin/cos/atan2/radians/
 * sqrt/acos) nên KHÔNG THỂ chạy công thức Haversine qua SQL. Test ở đây xác minh
 * CÔNG THỨC toán học đúng (bằng PHP) và không sinh NaN ở các edge-case:
 *   - hai điểm trùng nhau (a = 0)
 *   - hai điểm đối địa cầu (a = 1) — spherical law of cosines dùng acos sẽ ra NaN ở đây
 *   - hai điểm rất gần nhau (sai số dấu phẩy động)
 *
 * Test cho SQL (MySQL) nằm ở MissingChildSearchServiceTest / HandoverPointRepositoryTest,
 * được đánh dấu skip rõ ràng trên SQLite.
 */
class HaversineTest extends TestCase
{
    public function test_ha_noi_to_hcm_distance_is_about_1146_km(): void
    {
        $km = Haversine::distanceKm(21.0278, 105.8342, 10.7756, 106.7013);

        $this->assertFalse(is_nan($km));
        $this->assertGreaterThan(1130, $km);
        $this->assertLessThan(1160, $km);
    }

    public function test_ha_noi_to_da_nang_distance_is_about_620_km(): void
    {
        $km = Haversine::distanceKm(21.0278, 105.8342, 16.0544, 108.2022);

        $this->assertFalse(is_nan($km));
        $this->assertGreaterThan(595, $km);
        $this->assertLessThan(650, $km);
    }

    public function test_identical_points_distance_is_zero_not_nan(): void
    {
        $km = Haversine::distanceKm(10.0, 106.0, 10.0, 106.0);

        $this->assertFalse(is_nan($km));
        $this->assertSame(0.0, $km);
    }

    public function test_antipodal_points_distance_is_half_circumference_not_nan(): void
    {
        // Điểm đối địa cầu: a tính ra = 1. spherical law of cosines (acos) sẽ ra NaN
        // do acos(1 + sai số) > 1. Công thức atan2(sqrt(a), sqrt(1 - clamp(a))) phải ra ~πR.
        $km = Haversine::distanceKm(0.0, 0.0, 0.0, 180.0);

        $this->assertFalse(is_nan($km));
        // Nửa chu vi địa cầu: π * 6371 ≈ 20015 km
        $this->assertGreaterThan(19900, $km);
        $this->assertLessThan(20130, $km);
    }

    public function test_very_close_points_distance_is_small_and_finite(): void
    {
        $km = Haversine::distanceKm(10.0, 106.0, 10.001, 106.001);

        $this->assertFalse(is_nan($km));
        $this->assertGreaterThan(0.0, $km);
        $this->assertLessThan(0.2, $km);
    }

    public function test_sql_expression_uses_atan2_and_clamp_for_mysql(): void
    {
        [$sql, $bindings] = Haversine::sqlExpression('lat', 'lng', 10.8, 106.7, 'distance_km');

        $this->assertStringContainsString('atan2(', $sql);
        $this->assertStringContainsString('LEAST(1, GREATEST(0,', $sql);
        $this->assertStringNotContainsString('acos(', $sql);
        $this->assertStringContainsString('AS distance_km', $sql);

        // Bindings: lat, lat, lat, lng, lng
        $this->assertSame([10.8, 10.8, 10.8, 106.7, 106.7], $bindings);
    }
}
