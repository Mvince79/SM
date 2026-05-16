<?php

namespace App\Console\Commands;

use App\Models\MissingChild;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class FlushViewCounts extends Command
{
    protected $signature = 'views:flush';
    protected $description = 'Flush Redis view counts to database';

    public function handle(): void
    {
        $prefix = config('database.redis.options.prefix', '');
        $rawKeys = Redis::connection()->client()->keys($prefix . 'views:*');

        foreach ($rawKeys as $rawKey) {
            preg_match('/views:(\d+)/', $rawKey, $matches);
            if (!isset($matches[1]))
                continue;

            $id = (int) $matches[1];
            $count = (int) Cache::get("views:{$id}");

            if ($count > 0) {
                MissingChild::where('id', $id)->increment('view_count', $count);
                Cache::forget("views:{$id}");
            }
        }
    }
}