<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * MapDataController
 *
 * Returns simplified marker data for Leaflet map visualization.
 * GET /api/v1/map-data
 */
class MapDataController
{
    protected const CACHE_TTL = 180; // 3 minutes

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
     * Query Parameters:
     * - province_id: Filter by province
     * - type: missing|found|all (default: all)
     * - time_range: 24h|7d|30d|all (default: all)
     * - has_new_clue: 1|0
     * - sw_lat, sw_lng, ne_lat, ne_lng: Bounding box filter
     * - radius, lat, lng: Radius filter
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

        $cacheKey = $this->getCacheKey($validated);

        $markers = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->getMarkers($validated)
        );

        return response()->json($markers);
    }

    /**
     * Get marker data from missing_children and found_children.
     */
    protected function getMarkers(array $filters): array
    {
        $type = $filters['type'] ?? 'all';

        $hasRadiusFilter = isset(
            $filters['radius'],
            $filters['lat'],
            $filters['lng']
        );

        /*
        |--------------------------------------------------------------------------
        | MissingChild markers
        |--------------------------------------------------------------------------
        |
        | APPROVED: trẻ đang mất tích
        | FOUND: hồ sơ mất tích đã được đánh dấu tìm thấy
        |
        */

        $missingQuery = MissingChild::query()
            ->whereNull('deleted_at')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
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

        match ($type) {
            'missing' => $missingQuery->where('status', 'APPROVED'),
            'found' => $missingQuery->where('status', 'FOUND'),
            default => $missingQuery->whereIn('status', [
                'APPROVED',
                'FOUND',
            ]),
        };

        if (!empty($filters['province_id'])) {
            $missingQuery->where(
                'province_id',
                $filters['province_id']
            );
        }

        $timeRange = $filters['time_range'] ?? 'all';

        if (
            $timeRange !== 'all'
            && isset(self::TIME_RANGE_HOURS[$timeRange])
        ) {
            $missingQuery->where(
                'missing_date',
                '>=',
                now()->subHours(self::TIME_RANGE_HOURS[$timeRange])
            );
        }

        if (!empty($filters['has_new_clue'])) {
            $missingQuery->whereHas('clues', function ($query) {
                $query
                    ->whereNotNull('lat')
                    ->whereNotNull('lng')
                    ->where('is_hidden', false)
                    ->where(
                        'gps_shared_at',
                        '>=',
                        now()->subDays(7)
                    );
            });
        }

        if (!$hasRadiusFilter && $this->hasBoundingBox($filters)) {
            $this->applyBoundingBox($missingQuery, $filters);
        }

        $missingResults = $missingQuery->get();

        if ($hasRadiusFilter) {
            $missingResults = $this->filterCollectionByRadius(
                $missingResults,
                $filters
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FoundChild markers
        |--------------------------------------------------------------------------
        |
        | Chỉ lấy hồ sơ tìm thấy đã được duyệt và có tọa độ.
        |
        */

        $foundResults = collect();

        if (in_array($type, ['found', 'all'], true)) {
            $foundQuery = FoundChild::query()
                ->whereNull('deleted_at')
                ->where('status', 'APPROVED')
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->with(['primaryPhoto', 'province'])
                ->select([
                    'id',
                    'province_id',
                    'lat',
                    'lng',
                    'found_date',
                    'gender_estimate',
                    'age_estimate',
                    'condition_desc',
                    'current_location',
                    'status',
                ]);

            if (!empty($filters['province_id'])) {
                $foundQuery->where(
                    'province_id',
                    $filters['province_id']
                );
            }

            if (
                $timeRange !== 'all'
                && isset(self::TIME_RANGE_HOURS[$timeRange])
            ) {
                $foundQuery->where(
                    'found_date',
                    '>=',
                    now()->subHours(self::TIME_RANGE_HOURS[$timeRange])
                );
            }

            if (!$hasRadiusFilter && $this->hasBoundingBox($filters)) {
                $this->applyBoundingBox($foundQuery, $filters);
            }

            $foundResults = $foundQuery->get();

            if ($hasRadiusFilter) {
                $foundResults = $this->filterCollectionByRadius(
                    $foundResults,
                    $filters
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Transform MissingChild markers
        |--------------------------------------------------------------------------
        */

        $missingMarkers = $missingResults->map(function (MissingChild $child) {
            $fallbackPhoto = 'https://api.dicebear.com/7.x/initials/svg?seed='
                . urlencode($child->full_name);

            $photoUrl = $child->primaryPhoto?->path_thumb
                ? asset('storage/' . $child->primaryPhoto->path_thumb)
                : (
                    $child->primaryPhoto?->path_original
                    ? asset(
                        'storage/'
                        . $child->primaryPhoto->path_original
                    )
                    : $fallbackPhoto
                );

            return [
                'id' => $child->id,
                'case_code' => $child->case_code,
                'type' => $child->status === 'FOUND'
                    ? 'found'
                    : 'missing',
                'status' => $child->status,
                'lat' => (float) $child->lat,
                'lng' => (float) $child->lng,
                'name' => $child->full_name,
                'age' => $child->age_estimate,
                'gender' => null,
                'province_name' => $child->province?->name,
                'missing_date' => $child->missing_date?->format('d/m/Y'),
                'found_date' => null,
                'found_at' => $child->found_at?->format('d/m/Y'),
                'current_location' => null,
                'thumb_url' => $photoUrl,
                'photo_thumb_url' => $photoUrl,
                'url' => route('missing.detail', [
                    'id' => $child->id,
                ]),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Transform FoundChild markers
        |--------------------------------------------------------------------------
        */

        $foundMarkers = $foundResults->map(function (FoundChild $child) {
            $fallbackPhoto = 'https://api.dicebear.com/7.x/initials/svg?seed='
                . urlencode('Found Child ' . $child->id);

            $photoUrl = $child->primary_photo_url ?? $fallbackPhoto;

            return [
                'id' => $child->id,
                'case_code' => 'FOUND-' . $child->id,
                'type' => 'found',
                'status' => $child->status,
                'lat' => (float) $child->lat,
                'lng' => (float) $child->lng,
                'name' => 'Trẻ được tìm thấy',
                'age' => $child->age_estimate,
                'gender' => $child->gender_estimate,
                'province_name' => $child->province?->name,
                'missing_date' => null,
                'found_date' => $child->found_date?->format('d/m/Y'),
                'found_at' => null,
                'current_location' => $child->current_location,
                'thumb_url' => $photoUrl,
                'photo_thumb_url' => $photoUrl,
                'url' => route('found-children.show', [
                    'found_child' => $child->id,
                ]),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Merge both marker types
        |--------------------------------------------------------------------------
        */

        $markers = $missingMarkers
            ->concat($foundMarkers)
            ->values()
            ->all();

        return [
            'status' => 'success',
            'data' => $markers,
            'count' => count($markers),
        ];
    }

    /**
     * Check whether all bounding-box values are present.
     */
    private function hasBoundingBox(array $filters): bool
    {
        return isset(
            $filters['sw_lat'],
            $filters['sw_lng'],
            $filters['ne_lat'],
            $filters['ne_lng']
        );
    }

    /**
     * Apply viewport bounding-box filter.
     */
    private function applyBoundingBox($query, array $filters): void
    {
        $query
            ->whereBetween('lat', [
                min($filters['sw_lat'], $filters['ne_lat']),
                max($filters['sw_lat'], $filters['ne_lat']),
            ])
            ->whereBetween('lng', [
                min($filters['sw_lng'], $filters['ne_lng']),
                max($filters['sw_lng'], $filters['ne_lng']),
            ]);
    }

    /**
     * Filter an Eloquent collection by radius.
     */
    private function filterCollectionByRadius(
        $collection,
        array $filters
    ) {
        $originLat = (float) $filters['lat'];
        $originLng = (float) $filters['lng'];
        $radius = (float) $filters['radius'];

        return $collection
            ->filter(function ($child) use ($originLat, $originLng, $radius) {
                return $this->distanceKm(
                    $originLat,
                    $originLng,
                    (float) $child->lat,
                    (float) $child->lng
                ) <= $radius;
            })
            ->values();
    }

    /**
     * Generate cache key from filter parameters.
     */
    protected function getCacheKey(array $filters): string
    {
        $hash = hash('sha256', json_encode([
            'province_id' => $filters['province_id'] ?? null,
            'type' => $filters['type'] ?? 'all',
            'time_range' => $filters['time_range'] ?? 'all',
            'has_new_clue' => !empty($filters['has_new_clue']),

            'bounds' => [
                'sw_lat' => isset($filters['sw_lat'])
                    ? round((float) $filters['sw_lat'], 2)
                    : null,
                'sw_lng' => isset($filters['sw_lng'])
                    ? round((float) $filters['sw_lng'], 2)
                    : null,
                'ne_lat' => isset($filters['ne_lat'])
                    ? round((float) $filters['ne_lat'], 2)
                    : null,
                'ne_lng' => isset($filters['ne_lng'])
                    ? round((float) $filters['ne_lng'], 2)
                    : null,
            ],

            'radius' => isset($filters['radius'])
                ? round((float) $filters['radius'], 2)
                : null,

            'lat' => isset($filters['lat'])
                ? round((float) $filters['lat'], 6)
                : null,

            'lng' => isset($filters['lng'])
                ? round((float) $filters['lng'], 6)
                : null,
        ]));

        $version = CacheVersion::current(CacheVersion::MAP_DATA);

        return "map_data:v{$version}:{$hash}";
    }

    /**
     * Calculate distance between two coordinates using Haversine.
     */
    private function distanceKm(
        float $latA,
        float $lngA,
        float $latB,
        float $lngB
    ): float {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($latB - $latA);
        $lngDelta = deg2rad($lngB - $lngA);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latA))
            * cos(deg2rad($latB))
            * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm
            * 2
            * atan2(sqrt($a), sqrt(1 - $a));
    }
}