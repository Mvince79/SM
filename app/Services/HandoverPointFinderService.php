<?php

namespace App\Services;

use App\Repositories\HandoverPointRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HandoverPointFinderService
{
    public function __construct(
        protected HandoverPointRepository $repository
    ) {
    }

    public function findNearest(float $lat, float $lng, int $limit = 3): Collection
    {
        // Cache theo toạ độ làm tròn ~1km để tăng hit-rate, TTL 10 phút
        $cacheKey = sprintf(
            'handover_points:nearest:%s:%s:%d',
            round($lat, 2),
            round($lng, 2),
            $limit
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($lat, $lng, $limit) {
            return $this->repository->nearestTo($lat, $lng, $limit);
        });
    }
}