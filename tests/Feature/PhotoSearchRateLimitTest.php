<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Giới hạn tốc độ tìm kiếm bằng hình ảnh lấy từ Cài đặt hệ thống (mặc định 5
 * lần/giờ/IP) qua named limiter 'photo-search'. Test đảm bảo limiter được đăng
 * ký và thực sự giới hạn (lần thứ 6 trả 429).
 */
class PhotoSearchRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_search_rate_limiter_is_registered_and_enforced(): void
    {
        // Named limiter 'photo-search' phải được đăng ký (không nổ 500 khi route chạy).
        $this->assertNotNull(RateLimiter::limiter('photo-search'));

        $statuses = [];
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/photo-search');
            $statuses[] = $response->status();
        }

        // 5 lần đầu: hợp lệ (422 do thiếu ảnh, chưa tới quota) — KHÔNG phải 500
        // (500 nghĩa là limiter chưa đăng ký / lỗi runtime).
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotEquals(500, $statuses[$i], "Request #{$i} không được trả 500");
        }

        // Lần thứ 6: vượt quota mặc định 5 → 429 Too Many Requests.
        $this->assertEquals(429, $statuses[5], 'Lần thứ 6 phải bị giới hạn (429)');
    }
}
