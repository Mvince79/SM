<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MissingChild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
                'lat',
                'lng',
                'missing_date',
            ]);

        // Filter by province if provided
        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        // Filter by bounding box if provided
        if (!empty($filters['sw_lat']) && !empty($filters['ne_lat'])) {
            $query->whereBetween('lat', [
                min($filters['sw_lat'], $filters['ne_lat']),
                max($filters['sw_lat'], $filters['ne_lat']),
            ])->whereBetween('lng', [
                        min($filters['sw_lng'], $filters['ne_lng']),
                        max($filters['sw_lng'], $filters['ne_lng']),
                    ]);
        }

        $results = $query->get();

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
                'missing_date' => $child->missing_date?->format('d/m/Y'),
                'photo_thumb_url' => $child->primaryPhoto?->path_thumb,
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
        ]));

        return "map_data:{$hash}";
    }
}
