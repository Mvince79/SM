<?php

namespace App\Console\Commands;

use App\Jobs\EscalateUnconfirmedReunionJob;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * H-04 — Nhắc tự động gia đình xác nhận đoàn tụ.
 *
 * Chạy định kỳ (mỗi ngày). Với các hồ sơ status = HANDED_OVER:
 *  - Quá 3 ngày kể từ khi bàn giao → tạo NotificationEvent nhắc gia đình
 *    (chỉ tạo nếu chưa có nhắc nào trong 3 ngày gần nhất, tránh spam).
 *  - Quá 7 ngày → đẩy sang danh sách admin (dispatch EscalateUnconfirmedReunionJob
 *    → tạo escalation_queue), chỉ đẩy 1 lần (tránh trùng lặp escalation).
 *  - Đã liên hệ 3 lần trong vòng 14 ngày mà gia đình không phản hồi (hồ sơ vẫn
 *    HANDED_OVER) → chuyển status sang UNREACHABLE_FAMILY (hành động hệ thống,
 *    actor_role = SYSTEM nên không cần lý do).
 *  - Backstop: quá 14 ngày kể từ khi bàn giao cũng chuyển UNREACHABLE_FAMILY —
 *    cần cho các hồ sơ không gửi được lần liên hệ nào (vd: hồ sơ không có chủ
 *    sở hữu) nên số lần liên hệ mãi không đạt ngưỡng.
 *
 * Lưu ý về Carbon 3 (nesbot/carbon ^3): các hàm diffIn*() trả về float CÓ DẤU,
 * nên `$now->diffInDays($quaKhu)` cho giá trị ÂM. Vì vậy luôn tính theo chiều
 * `$since->diffInDays($now)` để ra số ngày đã trôi qua (dương).
 */
class RemindFamilyReunionConfirmation extends Command
{
    /** Số ngày kể từ khi bàn giao trước khi nhắc gia đình lần đầu. */
    public const REMIND_AFTER_DAYS = 3;

    /** Khoảng cách tối thiểu giữa 2 lần nhắc (tránh spam). */
    public const REMIND_INTERVAL_DAYS = 3;

    /** Số ngày kể từ khi bàn giao trước khi leo thang cho admin. */
    public const ESCALATE_AFTER_DAYS = 7;

    /** Cửa sổ (ngày) để đếm số lần đã liên hệ gia đình. */
    public const CONTACT_WINDOW_DAYS = 14;

    /** Số lần liên hệ không được phản hồi trước khi kết luận không liên lạc được. */
    public const MAX_CONTACT_ATTEMPTS = 3;

    protected $signature = 'reunion:remind-family';

    protected $description = 'Nhắc gia đình xác nhận đoàn tụ và leo thang cho admin nếu quá hạn.';

    public function handle(): int
    {
        $now = now();
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

            // Đã liên hệ đủ 3 lần trong 14 ngày mà gia đình vẫn không phản hồi
            // (hồ sơ còn HANDED_OVER) → không liên lạc được. Backstop theo số ngày
            // cho các hồ sơ không gửi được lần nhắc nào.
            if ($this->contactAttempts($case) >= self::MAX_CONTACT_ATTEMPTS
                || $days >= self::CONTACT_WINDOW_DAYS) {
                $case->forceFill(['status' => MissingChild::STATUS_UNREACHABLE_FAMILY])->save();
                $unreachable++;
                continue;
            }

            // Quá 7 ngày → leo thang cho admin (1 lần).
            if ($days >= self::ESCALATE_AFTER_DAYS && ! $this->alreadyEscalated($case)) {
                EscalateUnconfirmedReunionJob::dispatch($case->getKey());
                $escalated++;
            }

            // Quá 3 ngày → nhắc gia đình (1 lần / 3 ngày). Vẫn tiếp tục nhắc sau khi
            // đã leo thang: mỗi lần nhắc là 1 "lần liên hệ", cần đủ 3 lần mới được
            // kết luận UNREACHABLE_FAMILY.
            if ($days >= self::REMIND_AFTER_DAYS && ! $this->alreadyRemindedRecently($case)) {
                if ($this->sendFamilyReminder($case)) {
                    $reminded++;
                }
            }
        }

        $this->info("Reunion reminders: {$reminded} nhắc, {$escalated} leo thang, {$unreachable} không liên lạc được.");

        return self::SUCCESS;
    }

    /**
     * So sánh `>` (không phải `>=`): khi lần nhắc gần nhất cách đây ĐÚNG 3 ngày thì
     * khoảng cách tối thiểu đã thỏa, được nhắc tiếp — nếu dùng `>=` nhịp nhắc sẽ bị
     * trượt thành 4 ngày và không bao giờ đạt 3 lần liên hệ trong cửa sổ 14 ngày.
     */
    private function alreadyRemindedRecently(MissingChild $case): bool
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->where('occurred_at', '>', now()->subDays(self::REMIND_INTERVAL_DAYS))
            ->exists();
    }

    /**
     * Số lần đã liên hệ gia đình (mỗi NotificationEvent nhắc = 1 lần) trong cửa sổ 14 ngày.
     */
    private function contactAttempts(MissingChild $case): int
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->where('occurred_at', '>=', now()->subDays(self::CONTACT_WINDOW_DAYS))
            ->count();
    }

    /**
     * Đã leo thang cho admin trong cửa sổ 14 ngày chưa?
     *
     * Kiểm tra trên notification_events (do job luôn tạo) thay vì escalation_queue:
     * nếu hệ thống chưa có admin/moderator nào thì job không tạo dòng escalation_queue
     * nào cả, khiến kiểm tra theo escalation_queue luôn false và command sẽ dispatch
     * lại job mỗi ngày.
     */
    private function alreadyEscalated(MissingChild $case): bool
    {
        return $this->reunionEvents($case, NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION)
            ->where('occurred_at', '>=', now()->subDays(self::CONTACT_WINDOW_DAYS))
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

    private function sendFamilyReminder(MissingChild $case): bool
    {
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
            ],
            'triggered_by_user_id' => null,
            'occurred_at' => now(),
        ]);

        \App\Models\NotificationRecipient::create([
            'notification_event_id' => $event->getKey(),
            'user_id' => $owner->getKey(),
            'role_at_time' => $owner->role,
            'rendered_title' => 'Vui lòng xác nhận đã nhận lại con',
            'rendered_body' => "Hồ sơ {$case->case_code} ({$case->full_name}) đã được bàn giao. Vui lòng xác nhận đã đoàn tụ thành công để chúng tôi đóng hồ sơ.",
            'action_url' => route('missing.detail', ['id' => $case->getKey()]),
        ]);

        // Mốc lần nhắc gần nhất (hiển thị cho admin trong hồ sơ).
        $case->forceFill(['reunion_reminder_sent_at' => now()])->saveQuietly();

        return true;
    }
}
