<?php

namespace App\Services;

use App\Models\MissingChild;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MissingChildViewCountService
{
    private const KEY_PREFIX = 'missing_child:view_count:';
    private const FLUSH_THRESHOLD = 10;

    public function hit(int $missingChildId): void
    {
        $connection = Redis::connection('cache');
        $key = $this->key($missingChildId);

        $count = (int) $connection->incr($key);
        $connection->expire($key, 3600);

        if ($count >= self::FLUSH_THRESHOLD) {
            $this->flushKey($missingChildId, $key, $count, $connection);
        }
    }

    /**
     * Get the current pending view increments stored in Redis for a missing child.
     */
    public function getPendingCount(int $missingChildId): int
    {
        $connection = Redis::connection('cache');
        $key = $this->key($missingChildId);

        $val = $connection->get($key);

        return $val ? (int) $val : 0;
    }

    public function flushPending(): int
    {
        $connection = Redis::connection('cache');
        $keys = $connection->keys(self::KEY_PREFIX . '*');
        $updated = 0;

        foreach ($keys as $key) {
            $key = (string) $key;
            $missingChildId = (int) Str::afterLast($key, ':');
            $count = (int) $connection->get($key);

            if ($missingChildId > 0 && $count > 0) {
                $updated += $this->flushKey($missingChildId, $key, $count, $connection);
            }
        }

        return $updated;
    }

    private function flushKey(int $missingChildId, string $key, int $count, $connection): int
    {
        MissingChild::whereKey($missingChildId)->increment('view_count', $count);
        $connection->del($key);

        return $count;
    }

    private function key(int $missingChildId): string
    {
        return self::KEY_PREFIX . $missingChildId;
    }
}