<?php

namespace App\Observers;

use App\Jobs\DispatchProvinceCaseApprovedNotificationsJob;
use App\Models\MissingChild;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MissingChildObserver
{
    private function invalidateMapAndHomeCache(): void
    {
        try {
            CacheVersion::bump(CacheVersion::MAP_DATA);
            CacheVersion::bump(CacheVersion::HOME_HIGHLIGHTS);
        } catch (\Throwable $e) {
            Log::warning('MissingChildObserver: cache flush failed: ' . $e->getMessage());
        }
    }

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
     *
     * F-01 — Không gán review_level vào missing_children: cột review_level
     * thuộc bảng kyc_verifications (được ghi bởi ProcessKycJob), không tồn tại
     * trên missing_children. Việc gán trước đây ném ngoại lệ và bị nuốt bởi
     * try/catch, nên đã được gỡ bỏ.
     */
    public function created(MissingChild $missingChild): void
    {
        Log::info("MissingChild Observer (created): Generated case_code {$missingChild->case_code} for child ID {$missingChild->id}");

        // Tin mới (kể cả đang PENDING chờ duyệt) có thể đổi số liệu thống
        // kê ở trang chủ, và nếu status ban đầu đã là APPROVED thì cũng
        // cần xuất hiện ngay trên bản đồ — không đợi cache cũ hết TTL.
        $this->invalidateMapAndHomeCache();

        // Generate QR code PNG pointing to public detail URL and save path to model
        try {
            $qrService = app(\App\Services\QrCodeService::class);
            // Per spec: QR should point to /missing/{case_code}
            $detailUrl = url('/missing/' . $missingChild->case_code);
            $path = $qrService->generate($detailUrl, $missingChild->case_code);

            // Save relative path into model without firing events
            $missingChild->qr_code_path = $path;
            $missingChild->saveQuietly();
            Log::info("MissingChild Observer: QR code generated at {$path} for {$missingChild->case_code}");
        } catch (\Throwable $e) {
            Log::warning('QR generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the MissingChild "updated" event.
     */
    public function updated(MissingChild $missingChild): void
    {
        if ($missingChild->wasChanged('status') && $missingChild->status === 'APPROVED') {
            DispatchProvinceCaseApprovedNotificationsJob::dispatch($missingChild->getKey());
        }

        if ($missingChild->wasChanged(['status', 'lat', 'lng'])) {
            $this->invalidateMapAndHomeCache();
        }

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
            Log::warning('Activity log failed: ' . $e->getMessage());
        }

        Log::info("MissingChild Observer (updated): Updated child ID {$missingChild->id} ({$missingChild->case_code})");
    }

    /**
     * Handle the MissingChild "deleted" event (soft delete).
     */
    public function deleted(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }

    /**
     * Handle the MissingChild "restored" event.
     */
    public function restored(MissingChild $missingChild): void
    {
        $this->invalidateMapAndHomeCache();
    }
}
