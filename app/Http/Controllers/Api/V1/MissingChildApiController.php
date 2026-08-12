<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissingChildCollection;
use App\Http\Resources\MissingChildResource;
use App\Models\MissingChild;
use App\Models\HandoverPoint; // Import Model HandoverPoint
use App\Services\MissingChildSearchService;
use App\Services\MissingChildViewCountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MissingChildApiController extends Controller
{
    protected MissingChildSearchService $searchService;
    protected MissingChildViewCountService $viewCountService;

    public function __construct(MissingChildSearchService $searchService, MissingChildViewCountService $viewCountService)
    {
        $this->searchService = $searchService;
        $this->viewCountService = $viewCountService;
    }

    public function index(Request $request)
    {
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

        if (!empty($validated['sw_lat']) && !empty($validated['ne_lat'])) {
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

        $results = $this->searchService->search($validated);

        return new MissingChildCollection($results);
    }

    /**
     * GET /api/v1/missing-children/{id}
     */
    public function show(string|int $id)
    {
        $missing = MissingChild::where('id', $id)
            ->orWhere('case_code', $id)
            ->with(['province', 'primaryPhoto', 'photos', 'user'])
            ->firstOrFail();

        if ($missing->status !== 'APPROVED' && !Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'This case is not publicly available',
            ], 403);
        }

        // Tăng lượt xem
        $this->viewCountService->hit($missing->id);

        // Load danh sách HandoverPoint theo tỉnh/thành của vụ việc
        $provinceId = $missing->province_id ?? $missing->last_seen_province_id;

        $handoverPoints = HandoverPoint::where('is_active', true)
            ->when($provinceId, function ($query) use ($provinceId) {
                return $query->where('province_id', $provinceId);
            })
            ->orderBy('priority_order', 'asc')
            ->get();

        // Gán dữ liệu vào instance để API Resource có thể sử dụng
        $missing->setRelation('handover_points', $handoverPoints);

        return new MissingChildResource($missing);
    }

    protected function incrementViewCount(int $missingId): void
    {
        Cache::increment("views:{$missingId}");
    }

    protected function calculateBoundingBoxRadius(float $sw_lat, float $sw_lng, float $ne_lat, float $ne_lng): float
    {
        $center_lat = ($sw_lat + $ne_lat) / 2;
        $center_lng = ($sw_lng + $ne_lng) / 2;

        $lat1 = deg2rad($center_lat);
        $lat2 = deg2rad($ne_lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad($ne_lng) - deg2rad($center_lng);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * asin(sqrt($a));
        $radius = 6371 * $c;

        return $radius;
    }

    public function destroy(MissingChild $missingChild): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $missingChild);

        $missingChild->delete();

        return response()->json(['message' => 'Đã xóa tin đăng.'], 200);
    }
}