<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Support\CacheTags;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    protected const CACHE_TTL = 180; // 3 minutes

    public function index()
    {
        $highlights = Cache::tags([CacheTags::HOME_HIGHLIGHTS])->remember(
            'home:highlights',
            self::CACHE_TTL,
            function () {
                $sosKids = MissingChild::where('status', 'APPROVED')
                    ->whereNull('deleted_at')
                    ->with('primaryPhoto')
                    ->latest('missing_date')
                    ->take(5)
                    ->get();

                $urgentCases = MissingChild::where('status', 'APPROVED')
                    ->whereNull('deleted_at')
                    ->with('primaryPhoto', 'province')
                    ->latest('missing_date')
                    ->take(3)
                    ->get();

                $stats = [
                    'total_approved' => MissingChild::where('status', 'APPROVED')->count(),
                    'total_found' => MissingChild::whereIn('status', ['FOUND', 'CLOSED'])->count(),
                ];

                return compact('sosKids', 'urgentCases', 'stats');
            }
        );

        return view('home', $highlights);
    }
}