<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeoBoundaryService;
use Illuminate\Http\Request;

class GeoLookupController extends Controller
{
    /**
     * V-02 — POST /api/geo/lookup
     * Nhận {lat, lng}, trả {province, ward} hoặc {status: NOT_FOUND}.
     * Xử lý 100% nội bộ (point-in-polygon GeoJSON), không gọi API bên ngoài.
     */
    public function lookup(Request $request, GeoBoundaryService $service)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json(
            $service->lookup((float) $data['lat'], (float) $data['lng'])
        );
    }
}
