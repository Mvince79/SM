<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HorizonSmokeFailureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public string $runId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $cacheKey = "ops:horizon-smoke:{$this->runId}";

        if (Cache::add($cacheKey, true, now()->addHour())) {
            throw new RuntimeException("Intentional Horizon smoke-test failure [{$this->runId}].");
        }

        Cache::forget($cacheKey);

        Log::info('Horizon smoke-test job completed after retry.', [
            'run_id' => $this->runId,
        ]);
    }

    public function tags(): array
    {
        return ['ops:horizon-smoke', "run:{$this->runId}"];
    }
}
