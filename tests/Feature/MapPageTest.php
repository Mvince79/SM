<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trang bản đồ client: render được, bộ lọc Tỉnh thành hoạt động, và KHÔNG còn
 * bộ lọc khoảng cách (bán kính) đã bị gỡ.
 */
class MapPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_renders_with_refined_filters(): void
    {
        $response = $this->get('/map');

        $response->assertOk();
        $response->assertSee('Tỉnh thành');
        $response->assertSee('Thời gian mất tích');
        $response->assertSee('Có manh mối GPS mới nhất');
        // Bộ lọc bán kính (vẽ hình tròn trên bản đồ) đã bị gỡ.
        $response->assertDontSee('Đang lọc theo bán kính');
        $response->assertDontSee('leaflet-draw');
    }
}
