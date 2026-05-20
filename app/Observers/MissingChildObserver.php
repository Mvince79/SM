<?php

namespace App\Observers;

use App\Models\MissingChild;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissingChildObserver
{
    /**
     * Handle the MissingChild "created" event.
     */
    public function created(MissingChild $missingChild): void
    {
        DB::transaction(function () use ($missingChild) {
            $year = now()->year;
            $prefix = "MC-{$year}-";

            // Lock the table for writing and get the highest current case code for this year
            $maxCode = DB::table('missing_children')
                ->where('case_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->max('case_code');

            if ($maxCode) {
                $sequence = (int) substr($maxCode, -5) + 1;
            } else {
                $sequence = 1;
            }

            $caseCode = $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);

            // Update directly to avoid recursion
            DB::table('missing_children')
                ->where('id', $missingChild->id)
                ->update(['case_code' => $caseCode]);

            // Sync the model attribute for current process
            $missingChild->case_code = $caseCode;
        });

        // Log activity
        try {
            activity()
                ->performedOn($missingChild)
                ->event('created')
                ->withProperties(['attributes' => $missingChild->toArray()])
                ->log("Đã tạo tin tìm kiếm trẻ thất lạc: {$missingChild->full_name} ({$missingChild->case_code})");
        } catch (\Throwable $e) {
            Log::warning("Activity log failed: " . $e->getMessage());
        }

        Log::info("MissingChild Observer (created): Generated case_code {$missingChild->case_code} for child ID {$missingChild->id}");
    }

    /**
     * Handle the MissingChild "updated" event.
     */
    public function updated(MissingChild $missingChild): void
    {
        // Log activity
        try {
            activity()
                ->performedOn($missingChild)
                ->event('updated')
                ->withProperties([
                    'old' => array_intersect_key($missingChild->getOriginal(), $missingChild->getChanges()),
                    'attributes' => $missingChild->getChanges(),
                ])
                ->log("Đã cập nhật tin tìm kiếm trẻ thất lạc: {$missingChild->full_name} ({$missingChild->case_code})");
        } catch (\Throwable $e) {
            Log::warning("Activity log failed: " . $e->getMessage());
        }

        Log::info("MissingChild Observer (updated): Updated child ID {$missingChild->id} ({$missingChild->case_code})");
    }
}
