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
 * H-04 — Leo thang hồ sơ HANDED_OVER quá ngưỡng chưa được gia đình xác nhận.
 *
 * Tạo NotificationEvent + NotificationRecipient cho từng admin/moderator ĐI QUA
 * Notifier (chuông + email + push + realtime theo policy REUNION_ADMIN_ESCALATION),
 * đồng thời tạo một EscalationQueue (status PENDING) để admin theo dõi và xác nhận hộ.
 *
 * Dùng RecipientResolver::admins() (role ADMIN/MODERATOR + đang active) thay vì tự
 * lọc, giữ nhất quán với các luồng thông báo khác.
 */
class EscalateUnconfirmedReunionJob implements ShouldQueue
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

        if (! $case || $case->status !== MissingChild::STATUS_HANDED_OVER) {
            return;
        }

        $admins = $recipients->admins();

        if ($admins->isEmpty()) {
            // Không có admin/moderator nào active → không tạo escalation (tránh
            // command dispatch lại mỗi ngày vô ích). alreadyEscalated() dựa trên
            // notification_events, nên các lần sau cũng không tạo thêm.
            return;
        }

        $since = $case->handover_confirmed_at ?? $case->updated_at;
        $daysSinceHandover = $since ? (int) Carbon::parse($since)->diffInDays(now()) : 0;
        $reminderCount = NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey())
            ->count();

        $event = $notifier->send(
            NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION,
            $case,
            $admins,
            [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'owner_id' => $case->user?->getKey(),
                'days_since_handover' => $daysSinceHandover,
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
