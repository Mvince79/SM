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
 * DỮ LIỆU GEOJSON
 * ─────────────────────────────────────────────────────────────────────────────
 *  • storage/app/geo/vn-provinces-wards.geojson  — 34 feature cấp TỈNH, mỗi
 *    feature có properties {province, ward:null}. Dùng để xác định Tỉnh/Thành
 *    (tên chuẩn, khớp DB provinces → auto-fill province_id hoạt động).
 *  • storage/app/geo/vn-communes-simplified.geojson — 3.321 feature cấp XÃ/PHƯỜNG
 *    (sau sáp nhập 2025), mỗi feature có properties {ten, parent_ten} và geometry
 *    đã simplify (~6 MB). Dùng để xác định Xã/Phường. Được prefilter theo tỉnh
 *    vừa tìm được để tăng tốc point-in-polygon (không quét 3.321 feature mỗi lần).
 *
 * Khi thiếu file tỉnh hoặc file tỉnh là placeholder: mọi điểm trả NOT_FOUND /
 * UNVERIFIED → form sẽ chặn gửi (fail-safe có chủ đích). Khi thiếu file xã/phường:
 * province vẫn trả đúng, ward trả null (degrade nhẹ, không crash).
 *
 * Caching theo lat/lng làm tròn 4 chữ số (~11m), gắn fingerprint cả 2 file GeoJSON
 * để tự vô hiệu khi thay dữ liệu.
 */
class GeoBoundaryService
{
    private const CACHE_TTL = 60 * 24; // 24h

    private ?array $features = null;        // 34 tỉnh/thành (vn-provinces-wards.geojson)
    private ?array $communeFeatures = null; // 3.321 xã/phường (vn-communes-simplified.geojson)

    private ?bool $isPlaceholder = null;

    /**
     * @return array{status: string, province?: ?string, ward?: ?string}
     *
     * status:
     *   OK         — điểm nằm trong một đơn vị hành chính, có province (+ ward nếu dataset đủ chi tiết)
     *   NOT_FOUND  — điểm nằm NGOÀI lãnh thổ Việt Nam → form phải CHẶN gửi
     *   UNVERIFIED — chưa thể kết luận vì dataset tỉnh đang là placeholder → form KHÔNG chặn
     */
    public function lookup(float $lat, float $lng): array
    {
        // Cache key gắn fingerprint cả 2 dataset để tự vô hiệu khi thay file GeoJSON.
        $cacheKey = 'geo:lookup:' . $this->datasetFingerprint() . ':' . round($lat, 4) . ',' . round($lng, 4);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($lat, $lng) {
            // Dataset tỉnh placeholder KHÔNG đủ tin cậy để chặn người dùng → trả UNVERIFIED.
            if ($this->datasetIsPlaceholder()) {
                return ['status' => 'UNVERIFIED', 'province' => null, 'ward' => null];
            }

            // 1) Xác định Tỉnh/Thành từ file 34 tỉnh (polygon thô, tên chuẩn, khớp DB provinces).
            $provinceMatch = $this->findContainingFeature($this->loadFeatures(), $lat, $lng);

            if (! $provinceMatch) {
                return ['status' => 'NOT_FOUND'];
            }

            $province = $provinceMatch['province'] ?? null;

            // 2) Xác định Xã/Phường từ file xã/phường đã simplify, prefilter theo tỉnh
            //    vừa tìm được để tăng tốc (không quét 3.321 feature mỗi lần).
            $ward = $this->findWard($lat, $lng, $province);

            return [
                'status' => 'OK',
                'province' => $province,
                'ward' => $ward,
            ];
        });
    }

    /**
     * Đổi khi file GeoJSON được thay → cache lookup cũ tự bị bỏ qua.
     * Fingerprint = provinces_fp + '_' + communes_fp.
     */
    private function datasetFingerprint(): string
    {
        return $this->provincesFingerprint() . '_' . $this->communesFingerprint();
    }

    private function provincesFingerprint(): string
    {
        $path = storage_path('app/geo/vn-provinces-wards.geojson');

        if (! File::exists($path)) {
            return 'none';
        }

        return substr(md5(File::lastModified($path) . ':' . File::size($path)), 0, 10);
    }

    private function communesFingerprint(): string
    {
        $path = storage_path('app/geo/vn-communes-simplified.geojson');

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
     * Tìm Xã/Phường chứa điểm.
     *  - Prefilter: chỉ xét các xã/phường có parent_ten (tỉnh cha) khớp tỉnh vừa tìm
     *    được → point-in-polygon rất nhanh (vài chục ~ vài trăm feature thay vì 3.321).
     *  - Fallback: nếu prefilter không ghim được (tên tỉnh lệch / điểm sát ranh giới
     *    do khác biệt 2 dataset), quét TOÀN BỘ xã/phường để không bỏ sót.
     */
    private function findWard(float $lat, float $lng, ?string $province): ?string
    {
        $communes = $this->loadCommuneFeatures();

        if (empty($communes)) {
            return null;
        }

        $pn = $province !== null ? $this->normAdmin($province) : null;

        $filtered = [];

        if ($pn !== null) {
            foreach ($communes as $c) {
                $parent = $c['properties']['parent_ten'] ?? null;

                if ($this->normAdmin((string) $parent) === $pn) {
                    $filtered[] = $c;
                }
            }
        }

        $ward = $this->wardFromFeatures($filtered, $lat, $lng);

        if ($ward === null && $pn !== null) {
            $ward = $this->wardFromFeatures($communes, $lat, $lng);
        }

        return $ward;
    }

    private function wardFromFeatures(array $features, float $lat, float $lng): ?string
    {
        if (empty($features)) {
            return null;
        }

        $match = $this->findContainingFeature($features, $lat, $lng);

        return $match['ten'] ?? null;
    }

    /**
     * Tìm feature chứa điểm. Chọn feature có diện tích nhỏ nhất (cấp hành chính
     * nhỏ nhất) để ưu tiên Xã/Phường > Tỉnh khi cả hai chứa điểm.
     *
     * Chỉ có 2 cấp để phân giải (Tỉnh → Xã/Phường) — không có cấp huyện/quận
     * trung gian kể từ 01/07/2025.
     */
    private function findContainingFeature(array $features, float $lat, float $lng): ?array
    {
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

    private function loadCommuneFeatures(): array
    {
        if ($this->communeFeatures !== null) {
            return $this->communeFeatures;
        }

        $path = storage_path('app/geo/vn-communes-simplified.geojson');

        if (! File::exists($path)) {
            Log::warning('GeoBoundaryService: thiếu file xã/phường tại ' . $path . ' — ward sẽ trả null.');

            return $this->communeFeatures = [];
        }

        try {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('GeoBoundaryService: không đọc được GeoJSON xã/phường: ' . $e->getMessage());

            return $this->communeFeatures = [];
        }

        return $this->communeFeatures = $data['features'] ?? [];
    }

    /**
     * Chuẩn hóa tên đơn vị hành chính để so khớp bất chấp tiền tố/thể hiện,
     * bao gồm cả tiền tố đặc biệt của dataset xã/phường (Thủ đô, Thành phố, Tỉnh):
     *   "Cần Thơ"        ↔ "Thành phố Cần Thơ"
     *   "TP. Hồ Chí Minh" ↔ "Thành phố Hồ Chí Minh"
     *   "Hà Nội"         ↔ "Thủ đô Hà Nội"
     *   "An Giang"       ↔ "Tỉnh An Giang"
     * Bỏ tiền tố "TP." / "TP " / "Tỉnh " / "Thành phố " / "Thủ đô ", lower-case, trim.
     */
    private function normAdmin(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/^(tp\.?|tỉnh|thành phố|thủ đô)\s+/iu', '', $name)));
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
