<?php

namespace App\Observers;

use App\Models\MissingChild;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissingChildObserver
{
    /**
     * Handle the MissingChild "creating" event.
     * Chạy TRƯỚC khi INSERT → tránh race condition sinh code trùng.
     */
    public function creating(MissingChild $missingChild): void
    {
        $missingChild->case_code = $this->generateCaseCode();
    }

    /**
     * Sinh case_code duy nhất, thread-safe nhờ lockForUpdate bên trong transaction.
     */
    private function generateCaseCode(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $prefix = "MC-{$year}-";

            // lockForUpdate giờ có tác dụng vì record chưa tồn tại trong DB,
            // nên 2 request chạy song song sẽ lần lượt nhau, không đọc cùng maxCode.
            $maxCode = DB::table('missing_children')
                ->where('case_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->max('case_code');

            $sequence = $maxCode
                ? (int) substr($maxCode, -5) + 1
                : 1;

            return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Handle the MissingChild "created" event.
     * Chỉ còn log, không cần tạo case_code ở đây nữa.
     */
    public function created(MissingChild $missingChild): void
    {
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