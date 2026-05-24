<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;

class HomeController extends Controller
{
    public function index()
    {
        // 5 hồ sơ mới nhất đang cần tìm — hiển thị ở SOS bar
        $sosKids = MissingChild::where('status', 'APPROVED')
            ->whereNull('deleted_at')
            ->with('primaryPhoto')
            ->latest('missing_date')
            ->take(5)
            ->get();

        // 3 hồ sơ khẩn cấp nhất (mất tích trong 72h) — section "Đang cần tìm gấp"
        $urgentCases = MissingChild::where('status', 'APPROVED')
            ->whereNull('deleted_at')
            ->with('primaryPhoto', 'province')
            ->latest('missing_date')
            ->take(3)
            ->get();

        // Thống kê tổng quan
        $stats = [
            'total_approved' => MissingChild::where('status', 'APPROVED')->count(),
            'total_found' => MissingChild::whereIn('status', ['FOUND', 'CLOSED'])->count(),
        ];

        return view('home', compact('sosKids', 'urgentCases', 'stats'));
    }
}