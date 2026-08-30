<?php

namespace App\Jobs;

use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * H-05 — Leo thang hồ sơ FOUND quá ngưỡng chưa được gia đình xác nhận bàn giao.
 *
 * Song song với EscalateUnconfirmedReunionJob (dành cho bước đoàn tụ HANDED_OVER):
 * job này xử lý bước "bàn giao" — hồ sơ đã khớp (FOUND) nhưng gia đình chưa bấm
 * "Xác nhận bé đã được bàn giao".
 *
 * Tạo NotificationEvent + NotificationRecipient cho từng admin/moderator ĐI QUA
 * Notifier (chuông + email + push + realtime theo policy HANDOVER_ADMIN_ESCALATION),
 * đồng thời tạo một EscalationQueue (status PENDING) để admin theo dõi và xác nhận hộ.
 *
 * Dùng RecipientResolver::admins() (role ADMIN/MODERATOR + đang active) thay vì tự
 * lọc, giữ nhất quán với các luồng thông báo khác.
 */
class EscalateUnconfirmedHandoverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $missingChildId)
    {
        $this->onQueue('notifications');
    }

    public function handle(Notifier $notifier, RecipientResolver $recipients): void
    {
        $case = MissingChild::query()->with('user')->find($this->missingChildId);

        // Chỉ leo thang khi vẫn còn ở trạng thái FOUND (chưa gia đình xác nhận bàn giao).
        if (! $case || $case->status !== MissingChild::STATUS_FOUND) {
            return;
        }

        $admins = $recipients->admins();

        if ($admins->isEmpty()) {
            // Không có admin/moderator nào active → không tạo escalation (tránh
            // command dispatch lại mỗi ngày vô ích). alreadyEscalated() dựa trên
            // notification_events, nên các lần sau cũng không tạo thêm.
            return;
        }

        // Mốc tính từ lúc khớp được xác nhận (found_at) hoặc lúc cập nhật gần nhất.
        $since = $case->found_at ?? $case->updated_at;
        $daysSinceFound = $since ? (int) Carbon::parse($since)->diffInDays(now()) : 0;
        $reminderCount = NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey())
            ->count();

        $event = $notifier->send(
            NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION,
            $case,
            $admins,
            [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'owner_id' => $case->user?->getKey(),
                'days_since_handover' => $daysSinceFound,
                'reminder_count' => $reminderCount,
                'source' => NotificationEvent::SOURCE_SYSTEM,
            ],
            null,
        );

        if (! $event) {
            return;
        }

        // Tạo EscalationQueue cho từng recipient để admin theo dõi / xác nhận hộ.
        $sentRecipients = NotificationRecipient::query()
            ->where('notification_event_id', $event->getKey())
            ->get();

        foreach ($sentRecipients as $recipient) {
            EscalationQueue::firstOrCreate(
                ['notification_recipient_id' => $recipient->getKey()],
                ['due_at' => now()->addDays(7), 'status' => EscalationQueue::STATUS_PENDING],
            );
        }
    }
}
