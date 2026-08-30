<?php

namespace App\Http\Controllers;

use App\Models\MissingChild;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HeatmapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'time_range' => ['nullable', 'in:24h,7d,30d,all'],
        ]);

        $provinceId = $validated['province_id'] ?? null;
        $timeRange = $validated['time_range'] ?? 'all';

        $cacheKey = sprintf(
            'map:heatmap:v%s:%s:%s',
            CacheVersion::current(CacheVersion::MAP_DATA),
            $provinceId ?? 'all',
            $timeRange
        );

        $points = Cache::remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($provinceId, $timeRange): array {
                return MissingChild::query()
                    ->when(
                        $provinceId,
                        fn($query) => $query->where('province_id', $provinceId)
                    )
                    ->when(
                        $timeRange !== 'all',
                        function ($query) use ($timeRange) {
                            $hours = match ($timeRange) {
                                '24h' => 24,
                                '7d'  => 24 * 7,
                                '30d' => 24 * 30,
                                default => null,
                            };

                            if ($hours !== null) {
                                $query->where(
                                    'missing_date',
                                    '>=',
                                    now()->subHours($hours)
                                );
                            }
                        }
                    )
                    // Chỉ vẽ điểm cho hồ sơ đang active (APPROVED/FOUND). Hồ sơ đã
                    // đoàn tụ/đóng/từ chối (REUNITED/CLOSED/REJECTED/...) phải ẩn
                    // khỏi heatmap. Cache key đã gắn CacheVersion::MAP_DATA nên khi
                    // MissingChildObserver bump version lúc đổi status, heatmap tự
                    // làm mới (hồ sơ vừa đoàn tụ biến mất ngay, không đợi hết TTL).
                    ->activeForPublic()
                    ->whereNotNull('lat')
                    ->whereNotNull('lng')
                    ->select(['lat', 'lng'])
                    ->get()
                    ->map(static function (MissingChild $case): array {
                        return [
                            (float) $case->lat,
                            (float) $case->lng,
                            1,
                        ];
                    })
                    ->values()
                    ->all();
            }
        );

        return response()->json([
            'data' => $points,
            'meta' => [
                'province_id' => $provinceId,
                'time_range' => $timeRange,
                'count' => count($points),
                'cache_ttl_minutes' => 15,
            ],
        ]);
    }
}
