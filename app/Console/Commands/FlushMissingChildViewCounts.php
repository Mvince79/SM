<?php

namespace App\Console\Commands;

use App\Services\MissingChildViewCountService;
use Illuminate\Console\Command;

class FlushMissingChildViewCounts extends Command
{
    protected $signature = 'missing-children:flush-view-counts';

    protected $description = 'Flush Redis missing-child view counters into the database';

    public function handle(MissingChildViewCountService $service): int
    {
        $flushed = $service->flushPending();

        $this->info("Flushed {$flushed} view increments.");

        return self::SUCCESS;
    }
}