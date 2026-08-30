<?php

namespace App\Repositories;

use App\Models\HandoverPoint;
use App\Support\Haversine;
use Illuminate\Support\Collection;

class HandoverPointRepository
{
    public function all(): Collection
    {
        return HandoverPoint::active()->orderBy('priority_order')->get();
    }

    public function byProvince(int $provinceId): Collection
    {
        return HandoverPoint::active()
            ->byProvince($provinceId)
            ->orderBy('priority_order')
            ->get();
    }

    /**
     * Nền tảng để H-04 (Service Haversine) gọi lại — tránh trùng logic truy vấn.
     */
    public function nearestTo(float $lat, float $lng, int $limit = 3): Collection
    {
        [$distanceSql, $bindings] = Haversine::sqlExpression('lat', 'lng', $lat, $lng, 'distance');

        return HandoverPoint::active()
            ->selectRaw($distanceSql, $bindings)
            ->orderBy('distance')
            ->orderBy('priority_order')
            ->limit($limit)
            ->get();
    }
}