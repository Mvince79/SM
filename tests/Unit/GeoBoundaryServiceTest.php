<?php

namespace Tests\Unit;

use App\Services\GeoBoundaryService;
use Tests\TestCase;

/**
 * V-02 — Kiểm tra GeoBoundaryService dùng dataset ranh giới hành chính VN 2025 thật
 * (34 tỉnh/thành, 2 cấp Tỉnh→Xã). Xác nhận reverse-geocode trả đúng tên tỉnh thật,
 * không còn trả UNVERIFIED (từng là hành vi của file placeholder).
 */
class GeoBoundaryServiceTest extends TestCase
{
    private function service(): GeoBoundaryService
    {
        return app(GeoBoundaryService::class);
    }

    public function test_ha_noi_coordinate_returns_ha_noi_province(): void
    {
        $r = $this->service()->lookup(21.0278, 105.8342);

        $this->assertSame('OK', $r['status']);
        $this->assertSame('Hà Nội', $r['province']);
    }

    public function test_ho_chi_minh_coordinate_returns_ho_chi_minh_province(): void
    {
        $r = $this->service()->lookup(10.7756, 106.7013);

        $this->assertSame('OK', $r['status']);
        $this->assertSame('TP. Hồ Chí Minh', $r['province']);
    }

    public function test_can_tho_coordinate_returns_can_tho_province(): void
    {
        $r = $this->service()->lookup(10.0340, 105.7827);

        $this->assertSame('OK', $r['status']);
        $this->assertSame('Cần Thơ', $r['province']);
    }

    public function test_foreign_coordinate_returns_not_found(): void
    {
        // Bangkok, Thái Lan — nằm ngoài lãnh thổ Việt Nam.
        $r = $this->service()->lookup(13.7563, 100.5018);

        $this->assertSame('NOT_FOUND', $r['status']);
    }

    public function test_dataset_is_real_not_placeholder(): void
    {
        $r = $this->service()->lookup(21.0278, 105.8342);

        // Placeholder từng trả UNVERIFIED; dataset thật phải trả OK.
        $this->assertNotSame('UNVERIFIED', $r['status']);
    }
}
