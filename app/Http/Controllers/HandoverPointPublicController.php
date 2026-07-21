<?php

namespace App\Http\Controllers;

use App\Models\HandoverPoint;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HandoverPointPublicController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
        ]);

        $selectedProvinceId = isset($validated['province_id'])
            ? (int) $validated['province_id']
            : null;

        $selectedProvince = $selectedProvinceId
            ? Province::query()
                ->where('is_active', 1)
                ->find($selectedProvinceId)
            : null;

        $handoverPoints = HandoverPoint::query()
            ->active()
            ->with('province:id,name')
            ->when(
                $selectedProvince,
                fn ($query) => $query->byProvince($selectedProvince->id)
            )
            ->orderBy('priority_order')
            ->orderBy('name')
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
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription,
        ]);
    }
}