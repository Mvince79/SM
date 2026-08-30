<?php

namespace Tests\Feature;

use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * H-05 — Nhắc gia đình xác nhận "bé đã được bàn giao" (bước trước đoàn tụ), mirror
 * ReunionReminderTest nhưng cho trạng thái FOUND.
 *
 * CÁC TEST NÀY CƠ ĐỘNG THEO CONFIG: mốc nhắc / escalate / close và đơn vị thời
 * gian (notification.reminder_time_unit) đều đọc từ config, KHÔNG hardcode
 * [1,3,5]/5/7. Nhờ vậy dù bạn chỉnh cửa sổ nhắc ngắn (để test nhanh) hay về
 * bình thường, test vẫn xanh — không phải sửa lại số magic.
 */
class HandoverReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-26 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------- helpers

    /** Đơn vị thời gian cấu hình ('days' | 'hours' | 'minutes' | 'seconds'). */
    private function unit(): string
    {
        return config('notification.reminder_time_unit', 'days');
    }

    /** $ago đơn vị thời gian tính lùi từ hiện tại (theo unit cấu hình). */
    private function subSince(int $ago): Carbon
    {
        return now()->{"sub{$this->unit()}"}($ago);
    }

    /** Tiến thời gian thêm $n đơn vị (dùng để mô phỏng chạy định kỳ sau này). */
    private function advance(int $n): void
    {
        Carbon::setTestNow(now()->{"add{$this->unit()}"}($n));
    }

    private function milestones(): array
    {
        return config('notification.reminder_milestones', [1, 3, 5]);
    }

    private function maxMilestone(): int
    {
        return (int) max($this->milestones());
    }

    private function escalateAfter(): int
    {
        return (int) config('notification.escalate_after_days', 5);
    }

    private function closeAfter(): int
    {
        return (int) config('notification.close_after_days', 7);
    }

    /** Số mốc nhắc đã đến hạn khi đã trôi qua $elapsed đơn vị. */
    private function reachedCount(int $elapsed): int
    {
        return count(array_filter($this->milestones(), fn (int $m) => $m <= $elapsed));
    }

    /** Trạng thái kỳ vọng sau 1 lần chạy tại elapsed $elapsed (đã gửi $reached nhắc). */
    private function expectedStatus(int $elapsed): string
    {
        return ($elapsed >= $this->closeAfter() && $this->reachedCount($elapsed) >= 1)
            ? MissingChild::STATUS_UNREACHABLE_FAMILY
            : MissingChild::STATUS_FOUND;
    }

    // ------------------------------------------------------------- tests

    public function test_found_case_gets_milestone_reminders(): void
    {
        $elapsed = $this->closeAfter() - 1; // chưa tới close → còn FOUND
        $case = $this->createFoundCase($elapsed);

        Artisan::call('handover:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case));
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $case->user_id]);
        $this->assertSame($this->expectedStatus($elapsed), $case->fresh()->status);

        if ($this->reachedCount($elapsed) >= 1) {
            $this->assertNotNull($case->fresh()->reunion_reminder_sent_at);
        }
    }

    public function test_no_reminder_before_first_milestone(): void
    {
        $case = $this->createFoundCase(0);

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    public function test_reminders_are_not_duplicated_across_runs(): void
    {
        $elapsed = $this->closeAfter() - 1;
        $case = $this->createFoundCase($elapsed);

        Artisan::call('handover:remind-family');
        Artisan::call('handover:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case));
    }

    public function test_found_past_escalate_after_days_pushed_to_admin_queue(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $elapsed = $this->escalateAfter() + 1; // >= escalate, < close → leo thang
        $case = $this->createFoundCase($elapsed);

        Artisan::call('handover:remind-family');

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);

        $escalation = EscalationQueue::query()->with('notificationRecipient')->first();
        $this->assertNotNull($escalation, 'FOUND quá escalate_after_days phải vào escalation_queue.');
        $this->assertSame(EscalationQueue::STATUS_PENDING, $escalation->status);
        $this->assertSame($admin->getKey(), $escalation->notificationRecipient->user_id);
    }

    public function test_escalation_is_dispatched_only_once_per_case(): void
    {
        User::factory()->create(['role' => 'ADMIN']);
        $elapsed = $this->escalateAfter() + 1;
        $case = $this->createFoundCase($elapsed);

        Artisan::call('handover:remind-family');
        Artisan::call('handover:remind-family');

        $this->assertSame(1, NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_HANDOVER_ADMIN_ESCALATION)
            ->where('subject_id', $case->getKey())
            ->count());
        $this->assertSame(1, EscalationQueue::query()->count());
    }

    public function test_family_confirmation_stops_the_reminder_loop(): void
    {
        $case = $this->createFoundCase($this->closeAfter() - 1);
        $case->forceFill(['status' => MissingChild::STATUS_HANDED_OVER])->saveQuietly();

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    public function test_all_milestones_then_close_after_close_after_days(): void
    {
        $maxM = $this->maxMilestone();
        $case = $this->createFoundCase($maxM);

        // elapsed = maxM: mọi mốc đến hạn, chưa tới close → FOUND.
        Artisan::call('handover:remind-family');
        $this->assertSame($this->reachedCount($maxM), $this->reminderCount($case));
        $this->assertSame($this->expectedStatus($maxM), $case->fresh()->status);

        // Tiến tới closeAfter - 1: vẫn FOUND, số nhắc không đổi (idempotent).
        $this->advance($this->closeAfter() - 1 - $maxM);
        Artisan::call('handover:remind-family');
        $this->assertSame($this->reachedCount($this->closeAfter() - 1), $this->reminderCount($case));
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);

        // Tiến qua closeAfter: đã nhắc + quá hạn → UNREACHABLE_FAMILY.
        $this->advance(1);
        Artisan::call('handover:remind-family');
        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $case->fresh()->status);
    }

    // ------------------------------------------------------------------ utils

    private function reminderCount(MissingChild $case): int
    {
        return NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey())
            ->count();
    }

    /**
     * @param int $daysAgo Số đơn vị thời gian (theo notification.reminder_time_unit) đã trôi.
     */
    private function createFoundCase(int $daysAgo): MissingChild
    {
        $owner = User::factory()->create();
        $province = Province::query()->first() ?? Province::factory()->create();

        return MissingChild::withoutEvents(function () use ($owner, $province, $daysAgo): MissingChild {
            return MissingChild::query()->forceCreate([
                'user_id' => $owner->getKey(),
                'case_code' => 'MC-' . fake()->unique()->numerify('######'),
                'full_name' => fake()->name(),
                'gender' => 'UNKNOWN',
                'province_id' => $province->getKey(),
                'missing_date' => now()->subMonth(),
                'contact_name' => 'Người thân',
                'contact_phone' => '0905123456',
                'contact_relation' => 'PARENT',
                'status' => MissingChild::STATUS_FOUND,
                'found_at' => $this->subSince($daysAgo),
            ]);
        });
    }
}
