<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MissingChild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * MapDataController
 * 
 * Returns simplified marker data for Leaflet map visualization
 * GET /api/v1/map-data
 */
class MapDataController
{
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Map time_range filter value => số giờ tính từ hiện tại.
     */
    protected const TIME_RANGE_HOURS = [
        '24h' => 24,
        '7d' => 24 * 7,
        '30d' => 24 * 30,
    ];

    /**
     * GET /api/v1/map-data
     * 
     * Return JSON markers for Leaflet map
     * 
     * Query Parameters:
     * - province_id: Filter by province
     * - type: missing|found|all (default: all)
     * - time_range: 24h|7d|30d|all (default: all) — lọc theo missing_date
     * - has_new_clue: 1|0 — chỉ lấy case có manh mối GPS trong 7 ngày gần nhất
     * - sw_lat, sw_lng, ne_lat, ne_lng: Bounding box filter
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'nullable|integer|min:1|max:255',
            'type' => 'nullable|in:missing,found,all',
            'time_range' => 'nullable|in:24h,7d,30d,all',
            'has_new_clue' => 'nullable|boolean',
            'sw_lat' => 'nullable|numeric|between:-90,90',
            'sw_lng' => 'nullable|numeric|between:-180,180',
            'ne_lat' => 'nullable|numeric|between:-90,90',
            'ne_lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|required_with:lat,lng|numeric|min:0.1|max:200',
            'lat' => 'nullable|required_with:radius,lng|numeric|between:-90,90',
            'lng' => 'nullable|required_with:radius,lat|numeric|between:-180,180',
        ]);

        // Generate cache key
        $cacheKey = $this->getCacheKey($validated);

        // Try to get from cache
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        // Build query for markers
        $markers = $this->getMarkers($validated);

        // Cache result
        Cache::put($cacheKey, $markers, self::CACHE_TTL);

        return response()->json($markers);
    }

    /**
     * Get marker data from database
     */
    protected function getMarkers(array $filters): array
    {
        $type = $filters['type'] ?? 'all';

        // Query missing_children with photos
        $query = MissingChild::query()
            ->whereNull('deleted_at')
            ->with(['primaryPhoto', 'province'])
            ->select([
                'id',
                'case_code',
                'full_name',
                'age_estimate',
                'province_id',
                'lat',
                'lng',
                'missing_date',
                'status',
                'found_at',
            ]);

        // Trạng thái: Đang tìm (APPROVED) / Đã tìm thấy (FOUND) / Tất cả (cả hai)
        match ($type) {
            'missing' => $query->where('status', 'APPROVED'),
            'found' => $query->where('status', 'FOUND'),
            default => $query->whereIn('status', ['APPROVED', 'FOUND']),
        };

        // Filter by province if provided
        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        // Thời gian mất tích: 24h / 7 ngày / 30 ngày / Tất cả
        $timeRange = $filters['time_range'] ?? 'all';
        if ($timeRange !== 'all' && isset(self::TIME_RANGE_HOURS[$timeRange])) {
            $query->where('missing_date', '>=', now()->subHours(self::TIME_RANGE_HOURS[$timeRange]));
        }

        // Có manh mối GPS mới nhất: case có ít nhất 1 clue có tọa độ,
        // không bị ẩn, gửi trong 7 ngày gần nhất
        if (!empty($filters['has_new_clue'])) {
            $query->whereHas('clues', function ($q) {
                $q->whereNotNull('lat')
                    ->whereNotNull('lng')
                    ->where('is_hidden', false)
                    ->where('gps_shared_at', '>=', now()->subDays(7));
            });
        }

        // Radius search is more precise than a viewport bounding box, so prefer it
        // when both filter sets are present.
        $hasRadiusFilter = isset($filters['radius'], $filters['lat'], $filters['lng']);

        if ($hasRadiusFilter) {
            $query->whereNotNull('lat')
                ->whereNotNull('lng');
        } elseif (!empty($filters['sw_lat']) && !empty($filters['ne_lat'])) {
            $query->whereBetween('lat', [
                min($filters['sw_lat'], $filters['ne_lat']),
                max($filters['sw_lat'], $filters['ne_lat']),
            ])->whereBetween('lng', [
                        min($filters['sw_lng'], $filters['ne_lng']),
                        max($filters['sw_lng'], $filters['ne_lng']),
                    ]);
        }

        $results = $query->get();

        if ($hasRadiusFilter) {
            $originLat = (float) $filters['lat'];
            $originLng = (float) $filters['lng'];
            $radius = (float) $filters['radius'];

            $results = $results->filter(function ($child) use ($originLat, $originLng, $radius) {
                return $this->distanceKm(
                    $originLat,
                    $originLng,
                    (float) $child->lat,
                    (float) $child->lng,
                ) <= $radius;
            })->values();
        }

        // Transform to marker format
        $markers = $results->map(function ($child) {
            return [
                'id' => $child->id,
                'case_code' => $child->case_code,
                'type' => $child->status === 'FOUND' ? 'found' : 'missing',
                'status' => $child->status,
                'lat' => (float) $child->lat,
                'lng' => (float) $child->lng,
                'name' => $child->full_name,
                'age' => $child->age_estimate,
                'province_name' => $child->province?->name,
                'missing_date' => $child->missing_date?->format('d/m/Y'),
                'found_at' => $child->found_at?->format('d/m/Y'),

                'thumb_url' => 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($child->full_name),

                'photo_thumb_url' => 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($child->full_name),

                'url' => route('missing.detail', ['id' => $child->id]),
            ];
        })->values()->all();

        return [
            'status' => 'success',
            'data' => $markers,
            'count' => count($markers),
        ];
    }

    /**
     * Generate cache key from filter parameters
     */
    protected function getCacheKey(array $filters): string
    {
        $hash = hash('sha256', json_encode([
            'province_id' => $filters['province_id'] ?? null,
            'type' => $filters['type'] ?? 'all',
            'time_range' => $filters['time_range'] ?? 'all',
            'has_new_clue' => !empty($filters['has_new_clue']),
            'bounds' => [
                'sw_lat' => round($filters['sw_lat'] ?? 0, 2),
                'sw_lng' => round($filters['sw_lng'] ?? 0, 2),
                'ne_lat' => round($filters['ne_lat'] ?? 0, 2),
                'ne_lng' => round($filters['ne_lng'] ?? 0, 2),
            ],
            'radius' => isset($filters['radius']) ? round((float) $filters['radius'], 2) : null,
            'lat' => isset($filters['lat']) ? round((float) $filters['lat'], 6) : null,
            'lng' => isset($filters['lng']) ? round((float) $filters['lng'], 6) : null,
        ]));

        return "map_data:{$hash}";
    }

    private function distanceKm(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($latB - $latA);
        $lngDelta = deg2rad($lngB - $lngA);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}