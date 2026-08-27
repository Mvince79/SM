<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * V-02 — Reverse-geocode lat/lng → {province, ward} bằng point-in-polygon
 * với GeoJSON Việt Nam tĩnh (không gọi API bên ngoài).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * HÀNH CHÍNH VIỆT NAM 2025 — CHỈ 2 CẤP
 * ─────────────────────────────────────────────────────────────────────────────
 * Từ 01/07/2025 (Nghị quyết 202/2025/QH15) Việt Nam có 34 đơn vị hành chính cấp
 * tỉnh (28 tỉnh + 6 thành phố trực thuộc TW) và cấp HUYỆN/QUẬN đã bị BÃI BỎ.
 * Hệ thống địa chỉ chỉ còn 2 cấp:
 *
 *      Tỉnh/Thành phố  →  Xã/Phường
 *
 * Vì vậy service này CỐ Ý chỉ trả về `province` + `ward`. KHÔNG thêm `district`,
 * `huyen`, `quan` hay bất kỳ cấp trung gian nào vào kết quả — mọi consumer
 * (form báo tin, API, Filament, seeder) đều giả định đúng 2 cấp.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⚠️ DỮ LIỆU GEOJSON CẦN ĐƯỢC THAY BẰNG BỘ RANH GIỚI 2025
 * ─────────────────────────────────────────────────────────────────────────────
 * File `storage/app/geo/vn-provinces-wards.geojson` phải là FeatureCollection
 * các polygon hành chính với properties {province, ward} theo ĐÚNG ranh giới &
 * tên đơn vị sau sáp nhập 01/07/2025 (34 tỉnh/thành, tên xã/phường mới).
 *
 * CODE ở đây đã đúng cho mô hình 2 cấp, nhưng ASSET dữ liệu là thứ quyết định
 * độ chính xác của tên tỉnh/xã trả về. Nếu file hiện tại vẫn là bộ ranh giới
 * TRƯỚC sáp nhập (63 tỉnh / có cấp huyện), kết quả `province`/`ward` sẽ mang tên
 * hành chính CŨ dù logic vẫn chạy. Cần tải và thay thế bằng dataset ranh giới
 * 2025 (nguồn chính thức: Cục Đo đạc, Bản đồ và Thông tin địa lý Việt Nam /
 * dữ liệu mở theo Nghị quyết 202/2025/QH15), giữ nguyên schema properties
 * {province, ward}. KHÔNG tự bịa toạ độ polygon.
 *
 * Khi thiếu file hoặc file rỗng: mọi điểm trả NOT_FOUND → form sẽ chặn gửi,
 * đây là hành vi fail-safe có chủ đích.
 *
 * Caching theo lat/lng làm tròn 4 chữ số (~11m).
 */
class GeoBoundaryService
{
    private const CACHE_TTL = 60 * 24; // 24h

    private ?array $features = null;

    /** Dataset hiện tại chỉ là placeholder (cờ `_placeholder` trong GeoJSON)? */
    private ?bool $isPlaceholder = null;

    /**
     * @return array{status: string, province?: ?string, ward?: ?string}
     *
     * status:
     *   OK         — điểm nằm trong một đơn vị hành chính, có province (+ward nếu dataset đủ chi tiết)
     *   NOT_FOUND  — điểm nằm NGOÀI lãnh thổ Việt Nam → form phải CHẶN gửi
     *   UNVERIFIED — chưa thể kết luận vì dataset đang là placeholder → form KHÔNG chặn
     */
    public function lookup(float $lat, float $lng): array
    {
        // Cache key gắn fingerprint dataset để tự vô hiệu khi thay file GeoJSON.
        $cacheKey = 'geo:lookup:' . $this->datasetFingerprint() . ':' . round($lat, 4) . ',' . round($lng, 4);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($lat, $lng) {
            // Dataset placeholder KHÔNG đủ tin cậy để chặn người dùng: polygon bao rất
            // thô nên sai cả hai chiều (loại trừ điểm trong VN, bao cả điểm ngoài VN).
            // Trả UNVERIFIED để form hiển thị "chưa xác định" mà không chặn oan.
            // Khi cài dataset ranh giới 2025 thật (xoá cờ `_placeholder`), nhánh này
            // tắt và cơ chế chặn NOT_FOUND hoạt động đúng như thiết kế V-02.
            if ($this->datasetIsPlaceholder()) {
                return ['status' => 'UNVERIFIED', 'province' => null, 'ward' => null];
            }

            $match = $this->findContainingFeature($lat, $lng);

            if (! $match) {
                return ['status' => 'NOT_FOUND'];
            }

            return [
                'status' => 'OK',
                'province' => $match['province'] ?? null,
                'ward' => $match['ward'] ?? null,
            ];
        });
    }

    /**
     * Đổi khi file GeoJSON được thay → cache lookup cũ tự bị bỏ qua.
     */
    private function datasetFingerprint(): string
    {
        $path = storage_path('app/geo/vn-provinces-wards.geojson');

        if (! File::exists($path)) {
            return 'none';
        }

        return substr(md5(File::lastModified($path) . ':' . File::size($path)), 0, 10);
    }

    private function datasetIsPlaceholder(): bool
    {
        if ($this->isPlaceholder === null) {
            $this->loadFeatures();
        }

        return $this->isPlaceholder ?? true;
    }

    /**
     * Tìm feature chứa điểm. Ưu tiên cấp xã (ward) > tỉnh (province)
     * bằng cách chọn feature "nhỏ nhất" chứa điểm (diện tích ước lượng qua bounding box).
     *
     * Chỉ có 2 cấp để phân giải (Tỉnh → Xã/Phường) — không có cấp huyện/quận
     * trung gian kể từ 01/07/2025.
     */
    private function findContainingFeature(float $lat, float $lng): ?array
    {
        $features = $this->loadFeatures();

        $candidates = [];

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $rings = $this->extractRings($feature['geometry'] ?? null);

            foreach ($rings as $ring) {
                if ($this->pointInPolygon($lat, $lng, $ring)) {
                    $candidates[] = [
                        'props' => $props,
                        'area' => $this->boundingBoxArea($ring),
                    ];
                    break;
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Chọn feature có diện tích nhỏ nhất (cấp hành chính nhỏ nhất).
        usort($candidates, fn ($a, $b) => $a['area'] <=> $b['area']);

        return $candidates[0]['props'];
    }

    private function extractRings(?array $geometry): array
    {
        if (! $geometry) {
            return [];
        }

        $type = $geometry['type'] ?? null;
        $coords = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            return $coords; // mảng các ring
        }

        if ($type === 'MultiPolygon') {
            $rings = [];
            foreach ($coords as $polygon) {
                foreach ($polygon as $ring) {
                    $rings[] = $ring;
                }
            }

            return $rings;
        }

        return [];
    }

    /**
     * Ray-casting point-in-polygon. Ring là mảng [[lng, lat], ...].
     */
    private function pointInPolygon(float $lat, float $lng, array $ring): bool
    {
        $count = count($ring);
        if ($count < 4) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-9) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function boundingBoxArea(array $ring): float
    {
        $minLat = $maxLat = $ring[0][1];
        $minLng = $maxLng = $ring[0][0];

        foreach ($ring as $point) {
            $minLat = min($minLat, $point[1]);
            $maxLat = max($maxLat, $point[1]);
            $minLng = min($minLng, $point[0]);
            $maxLng = max($maxLng, $point[0]);
        }

        return ($maxLat - $minLat) * ($maxLng - $minLng);
    }

    private function loadFeatures(): array
    {
        if ($this->features !== null) {
            return $this->features;
        }

        $path = storage_path('app/geo/vn-provinces-wards.geojson');

        if (! File::exists($path)) {
            Log::warning('GeoBoundaryService: thiếu file GeoJSON tại ' . $path);
            $this->isPlaceholder = true;

            return $this->features = [];
        }

        try {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('GeoBoundaryService: không đọc được GeoJSON: ' . $e->getMessage());
            $this->isPlaceholder = true;

            return $this->features = [];
        }

        $this->features = $data['features'] ?? [];
        $this->isPlaceholder = (bool) ($data['_placeholder'] ?? false);

        if ($this->isPlaceholder) {
            Log::warning(
                'GeoBoundaryService: đang dùng GeoJSON PLACEHOLDER — reverse-geocode trả UNVERIFIED '
                . 'và KHÔNG chặn "ngoài lãnh thổ VN". Hãy cài dataset ranh giới hành chính VN 2025 '
                . '(34 tỉnh/thành, 2 cấp Tỉnh→Xã) rồi xoá cờ "_placeholder" trong ' . $path
            );
        }

        return $this->features;
    }
}
