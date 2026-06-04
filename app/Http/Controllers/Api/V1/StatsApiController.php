<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsApiController extends Controller
{
    public function index()
    {
        $data = Cache::remember('public_stats', now()->addMinutes(30), function () {
            $total = MissingChild::count();

            $found = MissingChild::where('status', 'FOUND')->count();

            $pending = MissingChild::where('status', 'PENDING')->count();

            $foundRate = $total > 0
                ? round(($found / $total) * 100, 1)
                : 0;

            $missingByProvince = MissingChild::query()
                ->join('provinces', 'missing_children.province_id', '=', 'provinces.id')
                ->select(
                    'provinces.name',
                    DB::raw('COUNT(missing_children.id) as count')
                )
                ->groupBy('provinces.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $trendMonthly = MissingChild::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->limit(12)
                ->get();

            return [
                'total' => $total,
                'found' => $found,
                'pending' => $pending,
                'found_rate_percent' => $foundRate,
                'missing_by_province' => $missingByProvince,
                'trend_monthly' => $trendMonthly,
            ];
        });

        return response()
            ->json($data)
            ->header('Cache-Control', 'public, max-age=1800');
    }
}