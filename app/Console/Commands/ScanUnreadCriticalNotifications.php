<?php

namespace App\Console\Commands;

use App\Models\EscalationQueue;
use App\Models\NotificationRecipient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanUnreadCriticalNotifications extends Command
{
    protected $signature = 'notifications:scan-unread-critical';

    protected $description = 'Quét notification_recipients chưa đọc quá ngưỡng cấu hình '
        . 'cho các event_type khẩn cấp, đẩy vào escalation_queue.';

    public function handle(): int
    {
        $thresholdMinutes = (int) config('notification.escalation_threshold_minutes');
        $criticalTypes = config('notification.critical_event_types', []);

        if (empty($criticalTypes)) {
            $this->info('Không có event_type nào được cấu hình là critical. Bỏ qua.');
            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes($thresholdMinutes);

        // Chỉ lấy recipient: chưa đọc + event thuộc loại critical + đã quá ngưỡng
        // + CHƯA có trong escalation_queue (tránh insert trùng, dù đã có unique constraint chặn ở DB).
        $overdueRecipients = NotificationRecipient::query()
            ->whereNull('read_at')
            ->whereHas('event', function ($q) use ($criticalTypes, $cutoff) {
                $q->whereIn('event_type', $criticalTypes)
                    ->where('occurred_at', '<=', $cutoff);
            })
            ->whereDoesntHave('escalationQueue')
            ->get();

        $created = 0;

        foreach ($overdueRecipients as $recipient) {
            try {
                EscalationQueue::create([
                    'notification_recipient_id' => $recipient->id,
                    'due_at' => $cutoff,
                    'status' => 'PENDING',
                ]);
                $created++;
            } catch (\Throwable $e) {
                // Race condition hiếm gặp: 2 lần chạy job trùng thời điểm cùng insert 1 recipient.
                // Unique constraint ở DB chặn được, chỉ cần log lại không cho job chết.
                Log::warning('Escalation insert failed (có thể do race condition)', [
                    'notification_recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Đã đẩy {$created} thông báo quá hạn vào escalation_queue.");

        return self::SUCCESS;
    }
}