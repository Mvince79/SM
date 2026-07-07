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
     * GET /api/v1/map-data
     * 
     * Return JSON markers for Leaflet map
     * 
     * Query Parameters:
     * - province_id: Filter by province
     * - type: missing|found|all (default: all)
     * - sw_lat, sw_lng, ne_lat, ne_lng: Bounding box filter
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'nullable|integer|min:1|max:255',
            'type' => 'nullable|in:missing,found,all',
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
        // Query missing_children with photos
        $query = MissingChild::where('status', 'APPROVED')
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
            ]);

        // Filter by province if provided
        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
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
                'type' => 'missing',
                'lat' => (float) $child->lat,
                'lng' => (float) $child->lng,
                'name' => $child->full_name,
                'age' => $child->age_estimate,
                'province_name' => $child->province?->name,
                'missing_date' => $child->missing_date?->format('d/m/Y'),

                'thumb_url' => 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($child->full_name),

                'photo_thumb_url' => 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($child->full_name),

                'url' => route('missing.detail', ['id' => $child->id]),
            ];
        })->values()->all();

        // TODO: Add found_children markers (T-15)
        // Query found_children and merge results

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
