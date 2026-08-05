<?php

namespace App\Observers;

use App\Models\FoundChild;
use App\Support\CacheTags;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FoundChildObserver
{
    private function invalidateMapCache(): void
    {
        try {
            Cache::tags([CacheTags::MAP_DATA])->flush();
        } catch (\Throwable $e) {
            Log::warning('FoundChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }

    public function created(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();
    }

    public function updated(FoundChild $foundChild): void
    {
        if ($foundChild->wasChanged(['status', 'lat', 'lng'])) {
            $this->invalidateMapCache();
        }
    }

    public function deleted(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();
    }

    public function restored(FoundChild $foundChild): void
    {
        $this->invalidateMapCache();
    }
}
