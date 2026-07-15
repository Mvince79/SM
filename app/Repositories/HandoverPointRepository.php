<?php

namespace App\Repositories;

use App\Models\HandoverPoint;
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
        return HandoverPoint::active()
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [$lat, $lng, $lat]
            )
            ->orderBy('distance')
            ->orderBy('priority_order')
            ->limit($limit)
            ->get();
    }
}