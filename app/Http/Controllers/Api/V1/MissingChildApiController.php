<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissingChildCollection;
use App\Http\Resources\MissingChildResource;
use App\Models\MissingChild;
use App\Services\MissingChildSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * MissingChildApiController
 * 
 * Handles API endpoints for missing children:
 * - GET /api/v1/missing-children (list with filters)
 * - GET /api/v1/missing-children/{id} (detail view)
 */
class MissingChildApiController extends Controller
{
    protected MissingChildSearchService $searchService;

    public function __construct(MissingChildSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * GET /api/v1/missing-children
     * 
     * List missing children with filters, pagination, and bounding box support
     * 
     * Query Parameters:
     * - q: Full-text search keyword
     * - province_id: Filter by province
     * - gender: MALE|FEMALE|UNKNOWN
     * - age_min, age_max: Age range
     * - missing_date_from, missing_date_to: Date range
     * - lat, lng, radius_km: Geographic search (Haversine)
     * - sw_lat, sw_lng, ne_lat, ne_lng: Bounding box (Leaflet map)
     * - sort: newest|oldest|near_me
     * - page: Page number (default 1)
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'q' => 'nullable|string|max:200',
            'province_id' => 'nullable|integer|min:1|max:255',
            'gender' => 'nullable|in:MALE,FEMALE,UNKNOWN',
            'age_min' => 'nullable|integer|min:0|max:100',
            'age_max' => 'nullable|integer|min:0|max:100',
            'missing_date_from' => 'nullable|date_format:Y-m-d',
            'missing_date_to' => 'nullable|date_format:Y-m-d',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:500',
            'sw_lat' => 'nullable|numeric|between:-90,90',
            'sw_lng' => 'nullable|numeric|between:-180,180',
            'ne_lat' => 'nullable|numeric|between:-90,90',
            'ne_lng' => 'nullable|numeric|between:-180,180',
            'sort' => 'nullable|in:newest,oldest,near_me',
            'page' => 'nullable|integer|min:1',
        ]);

        // Convert bounding box to lat/lng/radius format if provided
        if (!empty($validated['sw_lat']) && !empty($validated['ne_lat'])) {
            // Calculate center and radius from bounding box
            $center_lat = ($validated['sw_lat'] + $validated['ne_lat']) / 2;
            $center_lng = ($validated['sw_lng'] + $validated['ne_lng']) / 2;
            $radius_km = $this->calculateBoundingBoxRadius(
                $validated['sw_lat'],
                $validated['sw_lng'],
                $validated['ne_lat'],
                $validated['ne_lng']
            );

            $validated['lat'] = $center_lat;
            $validated['lng'] = $center_lng;
            $validated['radius_km'] = $radius_km;
            $validated['sort'] = 'near_me';
        }

        // Search using service
        $results = $this->searchService->search($validated);

        // Return collection with pagination metadata
        return new MissingChildCollection($results);
    }

    /**
     * GET /api/v1/missing-children/{id}
     * 
     * Get detail of a single missing child case
     * Increments view_count via Redis
     */
    public function show(string|int $id)
    {
        // Find by ID or case_code
        $missing = MissingChild::where('id', $id)
            ->orWhere('case_code', $id)
            ->with(['province', 'primaryPhoto', 'photos', 'user'])
            ->firstOrFail();

        // Check authorization: only APPROVED cases for guests
        if ($missing->status !== 'APPROVED' && !Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'This case is not publicly available',
            ], 403);
        }

        // Increment view count (async via Redis counter)
        $this->incrementViewCount($missing->id);

        return new MissingChildResource($missing);
    }

    /**
     * Increment view count using Redis counter
     * Batches updates every 10 views to reduce DB load
     */
    protected function incrementViewCount(int $missingId): void
    {
        Cache::increment("views:{$missingId}");
    }

    /**
     * Calculate radius (km) from bounding box coordinates
     * Used to convert Leaflet map bounds to distance-based search
     */
    protected function calculateBoundingBoxRadius(float $sw_lat, float $sw_lng, float $ne_lat, float $ne_lng): float
    {
        $center_lat = ($sw_lat + $ne_lat) / 2;
        $center_lng = ($sw_lng + $ne_lng) / 2;

        // Haversine distance: center to NE corner
        $lat1 = deg2rad($center_lat);
        $lat2 = deg2rad($ne_lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad($ne_lng) - deg2rad($center_lng);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * asin(sqrt($a));
        $radius = 6371 * $c; // Earth radius in km

        return $radius;
    }
}
