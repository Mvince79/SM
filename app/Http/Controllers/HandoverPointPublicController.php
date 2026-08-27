<?php

namespace App\Http\Controllers;

use App\Models\HandoverPoint;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HandoverPointPublicController extends Controller
{
    public function __construct(
        private \App\Repositories\HandoverPointRepository $repository
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $selectedProvinceId = isset($validated['province_id'])
            ? (int) $validated['province_id']
            : null;

        $hasCoordinates = isset($validated['lat'], $validated['lng'])
            && is_numeric($validated['lat'])
            && is_numeric($validated['lng']);

        $selectedProvince = $selectedProvinceId
            ? Province::query()
                ->where('is_active', 1)
                ->find($selectedProvinceId)
            : null;

        // G-02 — Khi có toạ độ, sắp theo khoảng cách tăng dần (gần → xa) và bôi nổi
        // 3 điểm đầu; không có toạ độ → giữ nguyên priority_order như cũ.
        $query = HandoverPoint::query()
            ->active()
            ->with('province:id,name');

        if ($hasCoordinates) {
            $lat = (float) $validated['lat'];
            $lng = (float) $validated['lng'];
            $query->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            );
        }

        $handoverPoints = $query
            ->when(
                $selectedProvince,
                fn ($q) => $q->byProvince($selectedProvince->id)
            )
            ->when(
                $hasCoordinates,
                fn ($q) => $q->orderBy('distance')->orderBy('priority_order'),
                fn ($q) => $q->orderBy('priority_order')->orderBy('name')
            )
            ->paginate(12)
            ->withQueryString();

        $provinces = Province::query()
            ->where('is_active', 1)
            ->whereHas('handoverPoints', function ($query) {
                $query->active();
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageTitle = $selectedProvince
            ? "Điểm tiếp nhận trẻ em uy tín tại {$selectedProvince->name}"
            : 'Danh sách điểm tiếp nhận trẻ em uy tín';

        $metaDescription = $selectedProvince
            ? "Danh sách các điểm bàn giao, tiếp nhận trẻ em uy tín tại {$selectedProvince->name}, gồm công an, UBND, Tổng đài 111 và các tổ chức có thẩm quyền."
            : 'Danh sách các điểm bàn giao và tiếp nhận trẻ em uy tín trên toàn quốc, gồm công an, UBND, Tổng đài 111 và các tổ chức bảo vệ trẻ em có thẩm quyền.';

        return view('handover-points.index', [
            'handoverPoints' => $handoverPoints,
            'provinces' => $provinces,
            'selectedProvince' => $selectedProvince,
            'selectedProvinceId' => $selectedProvinceId,
            'userLat' => $hasCoordinates ? $validated['lat'] : null,
            'userLng' => $hasCoordinates ? $validated['lng'] : null,
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription,
        ]);
    }

    /**
     * V-04 — API top-3 điểm tiếp nhận gần nhất theo toạ độ.
     * Trả JSON: mỗi điểm gồm name, address, hotline, type, lat, lng, distance_km.
     */
    public function nearest(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $points = $this->repository->nearestTo(
            (float) $data['lat'],
            (float) $data['lng'],
            3
        );

        return response()->json([
            'data' => $points->map(function (HandoverPoint $point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'address' => $point->address,
                    'hotline' => $point->hotline,
                    'type' => $point->type,
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                    'distance_km' => isset($point->distance)
                        ? round((float) $point->distance, 2)
                        : null,
                ];
            })->all(),
        ]);
    }
}