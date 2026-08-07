<?php

namespace App\Console\Commands;

use App\Jobs\HorizonSmokeFailureJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DispatchHorizonSmokeFailureCommand extends Command
{
    protected $signature = 'ops:horizon-smoke-failure
                            {--force : Allow dispatching the intentional failure in production}';

    protected $description = 'Dispatch a job that fails once and succeeds when retried from Horizon';

    public function handle(): int
    {
        if (config('queue.default') !== 'redis') {
            $this->error('Horizon only monitors Redis queues. Set QUEUE_CONNECTION=redis first.');

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Use --force to dispatch the intentional failure in production.');

            return self::FAILURE;
        }

        $runId = (string) Str::uuid();

        HorizonSmokeFailureJob::dispatch($runId);

        $this->info("Dispatched Horizon smoke-test run: {$runId}");
        $this->line('The first execution fails by design; retry it once from Horizon > Failed Jobs.');

        return self::SUCCESS;
    }
}
