<?php

namespace App\Console\Commands;

use App\Models\MissingChild;
use Illuminate\Console\Command;

class FillMissingSlugs extends Command
{
    protected $signature = 'app:fill-missing-slugs';

    protected $description = 'Fill slug for existing missing children records';

    public function handle(): int
    {
        $count = 0;

        MissingChild::whereNull('slug')
            ->orWhere('slug', '')
            ->chunkById(100, function ($missingChildren) use (&$count) {
                foreach ($missingChildren as $missingChild) {
                    $missingChild->slug = '';
                    $missingChild->save();

                    $count++;
                }
            });

        $this->info("Filled slugs for {$count} missing children.");

        return self::SUCCESS;
    }
}