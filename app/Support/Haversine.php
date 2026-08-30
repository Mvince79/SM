<?php

namespace App\Support;

/**
 * Công thức Haversine dùng chung (PHP + SQL) để tính khoảng cách giữa hai toạ độ.
 *
 * Dùng dạng `atan2(sqrt(a), sqrt(1 - clamp(a)))` — bền vững hơn spherical-law-of-cosines
 * (`acos`) vì không sinh NaN khi hai điểm gần trùng hoặc ở vị trí đối địa cầu: `a` được
 * ghìm vào [0, 1] trước khi lấy `sqrt(1 - a)`.
 *
 * Lưu ý: bản SQLite đi kèm PHP trong repo này KHÔNG biên dịch hàm toán học
 * (sin/cos/atan2/radians/sqrt/acos đều báo "no such function"), nên chuỗi SQL chỉ
 * chạy được trên MySQL. Trên SQLite ta đánh giá khoảng cách bằng `distanceKm()` (PHP)
 * hoặc fallback theo thứ tự khác — xem MissingChildSearchService / HandoverPointRepository.
 */
class Haversine
{
    public const EARTH_RADIUS_KM = 6371;

    /**
     * Khoảng cách (km) giữa hai toạ độ, tính bằng Haversine (PHP).
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        // Ghìm a vào [0, 1] để sqrt(1 - a) luôn hợp lệ (tránh NaN cho điểm gần trùng /
        // đối địa cầu do sai số dấu phẩy động).
        $a = min(1.0, max(0.0, $a));

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Trả về [SQL expression, bindings] tính cột khoảng cách (km) bằng Haversine,
     * chạy trên MySQL. Bindings theo thứ tự: lat, lat, lat, lng, lng.
     *
     * @return array{0: string, 1: array<int, float>}
     */
    public static function sqlExpression(string $latColumn, string $lngColumn, float $lat, float $lng, string $alias): array
    {
        $a = 'sin((radians(' . $latColumn . ') - radians(?)) / 2) * sin((radians(' . $latColumn . ') - radians(?)) / 2)'
            . ' + cos(radians(' . $latColumn . ')) * cos(radians(?)) * sin((radians(' . $lngColumn . ') - radians(?)) / 2) * sin((radians(' . $lngColumn . ') - radians(?)) / 2)';

        $sql = '(6371 * 2 * atan2(sqrt(' . $a . '), sqrt(1 - LEAST(1, GREATEST(0, ' . $a . '))))) AS ' . $alias;

        return [$sql, [$lat, $lat, $lat, $lng, $lng]];
    }
}
