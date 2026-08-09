<?php

namespace App\Console\Commands;

use App\Mail\CaseExpiringWarningMail;
use App\Models\MissingChild;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ArchiveExpiredCasesCommand extends Command
{
    protected $signature = 'cases:archive-expired';

    protected $description = 'Cảnh báo hồ sơ sắp hết hạn và lưu trữ hồ sơ đã quá hai năm';

    public function handle(): int
    {
        $now = now();
        $initialized = $this->initializeMissingExpirationDates();
        $archived = $this->archiveExpiredCases($now);
        $warned = $this->queueExpirationWarnings($now);

        $this->info("Đã khởi tạo hạn cho {$initialized} hồ sơ, lưu trữ {$archived} hồ sơ và xếp hàng {$warned} email cảnh báo.");

        return self::SUCCESS;
    }

    private function initializeMissingExpirationDates(): int
    {
        $initialized = 0;

        MissingChild::query()
            ->whereNull('expires_at')
            ->select(['id', 'created_at'])
            ->orderBy('id')
            ->chunkById(100, function ($children) use (&$initialized): void {
                foreach ($children as $child) {
                    $createdAt = $child->created_at ?? now();

                    $initialized += MissingChild::query()
                        ->whereKey($child->getKey())
                        ->whereNull('expires_at')
                        ->update(['expires_at' => $createdAt->copy()->addYears(2)]);
                }
            });

        return $initialized;
    }

    private function archiveExpiredCases($now): int
    {
        return MissingChild::query()
            ->whereIn('status', MissingChild::EXPIRABLE_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update([
                'status_before_archive' => DB::raw('status'),
                'status' => MissingChild::STATUS_ARCHIVED,
                'archived_at' => $now,
                'updated_at' => $now,
            ]);
    }

    private function queueExpirationWarnings($now): int
    {
        $warningDeadline = $now->copy()->addDays(MissingChild::EXPIRATION_WARNING_DAYS);
        $queued = 0;

        MissingChild::query()
            ->with('user:id,email,full_name')
            ->whereIn('status', MissingChild::EXPIRABLE_STATUSES)
            ->whereNull('expiration_warning_sent_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $warningDeadline)
            ->orderBy('id')
            ->chunkById(100, function ($children) use ($now, &$queued): void {
                foreach ($children as $child) {
                    if (blank($child->user?->email)) {
                        Log::warning('Không thể gửi cảnh báo hết hạn vì hồ sơ không có email chủ sở hữu.', [
                            'missing_child_id' => $child->getKey(),
                        ]);

                        continue;
                    }

                    $claimed = MissingChild::query()
                        ->whereKey($child->getKey())
                        ->whereNull('expiration_warning_sent_at')
                        ->update(['expiration_warning_sent_at' => $now]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    try {
                        $child->expiration_warning_sent_at = $now;
                        Mail::to($child->user)->queue(new CaseExpiringWarningMail($child));
                        $queued++;
                    } catch (Throwable $exception) {
                        MissingChild::query()
                            ->whereKey($child->getKey())
                            ->where('expiration_warning_sent_at', $now)
                            ->update(['expiration_warning_sent_at' => null]);

                        report($exception);
                    }
                }
            });

        return $queued;
    }
}
