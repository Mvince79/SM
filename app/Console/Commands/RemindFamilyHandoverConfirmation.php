<?php

namespace App\Console\Commands;

use App\Jobs\EscalateUnconfirmedHandoverJob;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Services\Notification\NotificationDeliveryService;
use App\Support\TimeUnit;
use Illuminate\Console\Command;

/**
 * H-05 — Nhắc tự động gia đình xác nhận "bé đã được bàn giao" (bước trước đoàn tụ),
 * theo mốc (milestone). Mirror RemindFamilyReunionConfirmation nhưng cho trạng thái
 * FOUND (đã khớp, chờ gia đình xác nhận bàn giao).
 *
 * Chạy định kỳ (mỗi ngày). Với các hồ sơ status = FOUND:
 *  - Với mỗi mốc trong config('notification.reminder_milestones') (mặc định
 *    D+1/3/5) đã tới hạn mà chưa gửi → tạo NotificationEvent nhắc gia đình
 *    (mỗi mốc một lần, milestone lưu trong payload để không gửi trùng).
 *  - Quá config('notification.escalate_after_days') (mặc định 5) ngày → đẩy
 *    sang admin (dispatch EscalateUnconfirmedHandoverJob), chỉ 1 lần.
 *  - Quá config('notification.close_after_days') (mặc định 7) ngày VÀ đã từng
 *    gửi ít nhất 1 lần nhắc → chuyển UNREACHABLE_FAMILY.
 *
 * Thiết kế "theo mốc" tương tự RemindFamilyReunionConfirmation: cron sập rồi chạy
 * lại vẫn bù đủ mốc, không trùng cũng không sót; hồ sơ chưa từng được nhắc không bị
 * đóng oan ở close_after_days.
 */
class RemindFamilyHandoverConfirmation extends Command
{
    protected $signature = 'handover:remind-family';

    protected $description = 'Nhắc gia đình xác nhận bàn giao (theo mốc) và leo thang cho admin nếu quá hạn.';

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
            ->where('status', MissingChild::STATUS_FOUND)
            ->with('user')
            ->get();

        foreach ($cases as $case) {
            // Mốc tính từ lúc khớp được xác nhận (found_at) hoặc cập nhật gần nhất.
            $since = $case->found_at ?? $case->updated_at;

            if (! $since) {
                continue;
            }

            // Chiều tính: từ lúc khớp ĐẾN hiện tại → số ĐƠN VỊ (ngày/giờ/phút, theo
            // config notification.reminder_time_unit) đã trôi qua. Mặc định 'days'.
            $elapsed = TimeUnit::elapsedSince($since, $now);

            // Gửi từng mốc đến hạn mà chưa gửi (mỗi mốc 1 lần, idempotent theo milestone).
            foreach ($milestones as $milestone) {
                if ($elapsed >= $milestone && ! $this->alreadyRemindedMilestone($case, $milestone)) {
                    if ($this->sendFamilyReminder($case, $milestone, $delivery)) {
                        $reminded++;
                    }
                }
            }

            // Số lần đã liên hệ (tổng số mốc nhắc đã gửi).
            $attempts = $this->contactAttempts($case);

            // Đóng hồ sơ chỉ khi đã quá close_after_days VÀ đã từng gửi ít nhất 1 lần
            // nhắc (tránh đóng oan khi cron sập / không liên hệ được lần nào).
            if ($elapsed >= $closeAfter && $attempts >= 1) {
                $case->forceFill(['status' => MissingChild::STATUS_UNREACHABLE_FAMILY])->save();
                $unreachable++;
                continue;
            }

            // Quá escalate_after_days → leo thang cho admin (1 lần).
            if ($elapsed >= $escalateAfter && ! $this->alreadyEscalated($case)) {
                EscalateUnconfirmedHandoverJob::dispatch($case->getKey());
                $escalated++;
            }
        }

        $this->info("Handover reminders: {$reminded} nhắc, {$escalated} leo thang, {$unreachable} không liên lạc được.");

        return self::SUCCESS;
    }

    /**
     * Đã gửi nhắc cho mốc cụ thể (theo payload.milestone) chưa?
     */
    private function alreadyRemindedMilestone(MissingChild $case, int $milestone): bool
    {
        return $this->handoverEvents($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER)
            ->where('payload->milestone', $milestone)
            ->exists();
    }

    /**
     * Tổng số lần đã liên hệ gia đình (mỗi NotificationEvent nhắc = 1 lần).
     */
    private function contactAttempts(MissingChild $case): int
    {
        return $this->handoverEvents($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER)
            ->count();
    }

    /**
     * Đã leo thang cho admin chưa? Kiểm tra trên notification_events (do job luôn
     * tạo) thay vì escalation_queue: nếu không có admin/moderator nào, job không
     * tạo dòng escalation_queue → kiểm tra theo queue luôn false, dispatch lại mỗi ngày.
     */
    private function alreadyEscalated(MissingChild $case): bool
    {
        return $this->handoverEvents($case, NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION)
            ->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<NotificationEvent>
     */
    private function handoverEvents(MissingChild $case, string $eventType)
    {
        return NotificationEvent::query()
            ->where('event_type', $eventType)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey());
    }

    /**
     * Gửi 1 nhắc cho mốc cụ thể qua đường ống hợp nhất (chuông + mail + push +
     * realtime theo policy HANDOVER_FAMILY_REMINDER). Mỗi mốc là 1 NotificationEvent
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
            'event_type' => NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER,
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
