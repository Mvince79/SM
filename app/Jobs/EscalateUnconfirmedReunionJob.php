<?php

namespace App\Jobs;

use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * H-04 — Leo thang hồ sơ HANDED_OVER quá 7 ngày chưa được gia đình xác nhận.
 *
 * Tạo NotificationEvent + NotificationRecipient cho từng admin/moderator và
 * một EscalationQueue (status PENDING) để admin theo dõi và xác nhận hộ.
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

    public function handle(): void
    {
        $case = MissingChild::query()->with('user')->find($this->missingChildId);

        if (! $case || $case->status !== MissingChild::STATUS_HANDED_OVER) {
            return;
        }

        $event = NotificationEvent::create([
            'event_type' => NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
            'payload' => [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'owner_id' => $case->user?->getKey(),
            ],
            'triggered_by_user_id' => null,
            'occurred_at' => now(),
        ]);

        $admins = User::query()
            ->whereIn('role', ['ADMIN', 'MODERATOR'])
            ->where('status', '!=', 'BANNED')
            ->get();

        foreach ($admins as $admin) {
            $recipient = NotificationRecipient::create([
                'notification_event_id' => $event->getKey(),
                'user_id' => $admin->getKey(),
                'role_at_time' => $admin->role,
                'rendered_title' => 'Cần xác minh đoàn tụ hộ (gia đình không phản hồi)',
                'rendered_body' => "Hồ sơ {$case->case_code} ({$case->full_name}) đã bàn giao quá 7 ngày nhưng gia đình chưa xác nhận. Vui lòng liên hệ xác minh.",
                'action_url' => route('filament.admin.resources.missing-children.view', ['record' => $case->getKey()]),
            ]);

            EscalationQueue::create([
                'notification_recipient_id' => $recipient->getKey(),
                'due_at' => now()->addDays(7),
                'status' => EscalationQueue::STATUS_PENDING,
            ]);
        }
    }
}
