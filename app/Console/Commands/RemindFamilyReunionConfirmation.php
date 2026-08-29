<?php

namespace App\Console\Commands;

use App\Jobs\EscalateUnconfirmedReunionJob;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * H-04 — Nhắc tự động gia đình xác nhận đoàn tụ, theo mốc (milestone).
 *
 * Chạy định kỳ (mỗi ngày). Với các hồ sơ status = HANDED_OVER:
 *  - Với mỗi mốc trong config('notification.reminder_milestones') (mặc định
 *    D+1/3/5) đã tới hạn mà chưa gửi → tạo NotificationEvent nhắc gia đình
 *    (mỗi mốc 1 lần, milestone lưu trong payload để không gửi trùng).
 *  - Quá config('notification.escalate_after_days') (mặc định 5) ngày → đẩy
 *    sang admin (dispatch EscalateUnconfirmedReunionJob), chỉ 1 lần.
 *  - Quá config('notification.close_after_days') (mặc định 7) ngày VÀ đã từng
 *    gửi ít nhất 1 lần nhắc → chuyển UNREACHABLE_FAMILY.
 *
 * Thiết kế "theo mốc" thay vì phép trừ khoảng cách đơn giản:
 *  - Cron sập một thời gian rồi chạy lại: mọi mốc đến hạn chưa gửi sẽ được bù,
 *    không bỏ sót cũng không gửi trùng (mỗi mốc chỉ 1 event).
 *  - Hồ sơ chưa từng được gửi nhắc nào (cron chết từ lúc bàn giao, hoặc không
 *    có chủ sở hữu) KHÔNG bị đóng oan ở close_after_days — đúng yêu cầu "cron
 *    sập không giết oan hồ sơ".
 *
 * Lưu ý Carbon 3 (nesbot/carbon ^3): diffInDays() trả về float CÓ DẤU, nên
 * luôn tính $since->diffInDays($now) để ra số ngày đã trôi qua (dương).
 */
class RemindFamilyReunionConfirmation extends Command
{
    protected $signature = 'reunion:remind-family';

    protected $description = 'Nhắc gia đình xác nhận đoàn tụ (theo mốc) và leo thang cho admin nếu quá hạn.';

    public function handle(NotificationDeliveryService $delivery): int
    {
        $now = now();

        $milestones = config('notification.reminder_milestones', [1, 3, 5]);
        $escalateAfter = (int) config('notification.escalate_after_days', 5);
        $closeAfter = (int) config('notification.close_after_days', 7);

        $reminded = 0;
        $escalated = 0;
        $unreachable = 0;

        $cases = MissingChild::query()
            ->where('status', MissingChild::STATUS_HANDED_OVER)
            ->with('user')
            ->get();

        foreach ($cases as $case) {
            $since = $case->handover_confirmed_at ?? $case->updated_at;

            if (! $since) {
                continue;
            }

            // Chiều tính: từ mốc bàn giao ĐẾN hiện tại → số ngày đã trôi qua (dương).
            $days = (int) Carbon::parse($since)->diffInDays($now);

            // Gửi từng mốc đến hạn mà chưa gửi (mỗi mốc 1 lần, idempotent theo milestone).
            foreach ($milestones as $milestone) {
                if ($days >= $milestone && ! $this->alreadyRemindedMilestone($case, $milestone)) {
                    if ($this->sendFamilyReminder($case, $milestone, $delivery)) {
                        $reminded++;
                    }
                }
            }

            // Số lần đã liên hệ (tổng số mốc nhắc đã gửi).
            $attempts = $this->contactAttempts($case);

            // Đóng hồ sơ chỉ khi đã quá close_after_days VÀ đã từng gửi ít nhất 1 lần
            // nhắc (tránh đóng oan khi cron sập / không liên hệ được lần nào).
            if ($days >= $closeAfter && $attempts >= 1) {
                $case->forceFill(['status' => MissingChild::STATUS_UNREACHABLE_FAMILY])->save();
                $unreachable++;
                continue;
            }

            // Quá escalate_after_days → leo thang cho admin (1 lần).
            if ($days >= $escalateAfter && ! $this->alreadyEscalated($case)) {
                EscalateUnconfirmedReunionJob::dispatch($case->getKey());
                $escalated++;
            }
        }

        $this->info("Reunion reminders: {$reminded} nhắc, {$escalated} leo thang, {$unreachable} không liên lạc được.");

        return self::SUCCESS;
    }

    /**
     * Đã gửi nhắc cho mốc cụ thể (theo payload.milestone) chưa?
     *
     * So sánh `>` (không `>=`) để khi lần nhắc gần nhất cùng mốc cách đúng 1 ngày
     * vẫn được gửi tiếp, không trượt nhịp.
     */
    private function alreadyRemindedMilestone(MissingChild $case, int $milestone): bool
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->where('payload->milestone', $milestone)
            ->exists();
    }

    /**
     * Tổng số lần đã liên hệ gia đình (mỗi NotificationEvent nhắc = 1 lần).
     */
    private function contactAttempts(MissingChild $case): int
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->count();
    }

    /**
     * Đã leo thang cho admin chưa? Kiểm tra trên notification_events (do job luôn
     * tạo) thay vì escalation_queue: nếu không có admin/moderator nào, job không
     * tạo dòng escalation_queue → kiểm tra theo queue luôn false, dispatch lại mỗi ngày.
     */
    private function alreadyEscalated(MissingChild $case): bool
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION)
            ->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<NotificationEvent>
     */
    private function reunionEvents(MissingChild $case, string $eventType)
    {
        return NotificationEvent::query()
            ->where('event_type', $eventType)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey());
    }

    /**
     * Gửi 1 nhắc cho mốc cụ thể qua đường ống hợp nhất (chuông + mail + push +
     * realtime theo policy REUNION_FAMILY_REMINDER). Mỗi mốc là 1 NotificationEvent
     * riêng biệt (payload.milestone) nên không trùng với Notifier::send (firstOrCreate
     * theo subject) — tạo event trực tiếp rồi deliver qua NotificationDeliveryService.
     */
    private function sendFamilyReminder(
        MissingChild $case,
        int $milestone,
        NotificationDeliveryService $delivery,
    ): bool {
        $owner = $case->user;

        if (! $owner) {
            return false;
        }

        $event = NotificationEvent::create([
            'event_type' => NotificationEvent::TYPE_REUNION_FAMILY_REMINDER,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
            'payload' => [
                'case_code' => $case->case_code,
                'full_name' => $case->full_name,
                'milestone' => $milestone,
                'source' => NotificationEvent::SOURCE_SYSTEM,
            ],
            'triggered_by_user_id' => null,
            'occurred_at' => now(),
        ]);

        // Gắn subject để ConfigDrivenStrategy sinh đúng action_url (trang hồ sơ).
        $event->setRelation('subject', $case);

        $delivery->deliver($event, [$owner]);

        // Mốc lần nhắc gần nhất (hiển thị cho admin trong hồ sơ).
        $case->forceFill(['reunion_reminder_sent_at' => now()])->saveQuietly();

        return true;
    }
}
