<?php

namespace Tests\Feature;

use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * H-04/H-05 — Đơn vị thời gian cho luồng nhắc/leo thang là CẤU HÌNH ĐƯỢC
 * (config notification.reminder_time_unit), KHÔNG còn hardcode diffInDays().
 *
 * Mặc định 'days' giữ nguyên hành vi production (được phủ bởi
 * HandoverReminderTest / ReunionReminderTest). Ở đây chỉ xác nhận: đổi unit
 * sang 'minutes' thì mốc nhắc kích hoạt theo PHÚT thay vì NGÀY — cả 2 luồng
 * HANDOVER (FOUND) và REUNION (HANDED_OVER).
 *
 * CÁC TEST NÀY CƠ ĐỘNG THEO CONFIG: mốc nhắc / escalate / close và đơn vị thời
 * gian đều đọc từ config thay vì hardcode [1,3,5]/5/7 hay số 2/4 magic. Dù bạn
 * đổi cửa sổ nhắc ngắn (test nhanh) hay về bình thường, test vẫn xanh — không
 * phải sửa lại con số.
 */
class ReminderTimeUnitConfigTest extends TestCase
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

    // ---------------------------------------------------------- HANDOVER

    public function test_handover_reminder_triggers_by_minutes_when_unit_is_minutes(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        // elapsed = mốc lớn nhất → mọi mốc (theo config) đều đến hạn, < close → còn FOUND.
        $elapsed = $this->maxMilestone();
        $case = $this->createFoundCase($elapsed);

        Artisan::call('handover:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER));
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);
    }

    public function test_handover_reminder_no_trigger_before_first_minute_milestone(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        // 0 đơn vị (phút) trôi qua → chưa tới mốc đầu tiên → không nhắc nào.
        $case = $this->createFoundCase(0);

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER));
    }

    // ---------------------------------------------------------- REUNION

    public function test_reunion_reminder_triggers_by_minutes_when_unit_is_minutes(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $elapsed = $this->maxMilestone();
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER));
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);
    }

    public function test_reunion_reminder_no_trigger_before_first_minute_milestone(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $case = $this->createHandedOverCase(0);

        Artisan::call('reunion:remind-family');

        $this->assertSame(0, $this->reminderCount($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER));
    }

    // ------------------------------------------------------------------ utils

    private function reminderCount(MissingChild $case, string $eventType): int
    {
        return NotificationEvent::query()
            ->where('event_type', $eventType)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey())
            ->count();
    }

    private function createFoundCase(int $minutesAgo): MissingChild
    {
        $owner = User::factory()->create();
        $province = Province::query()->first() ?? Province::factory()->create();

        return MissingChild::withoutEvents(function () use ($owner, $province, $minutesAgo): MissingChild {
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
                'found_at' => $this->subSince($minutesAgo),
            ]);
        });
    }

    private function createHandedOverCase(int $minutesAgo): MissingChild
    {
        $owner = User::factory()->create();
        $province = Province::query()->first() ?? Province::factory()->create();

        return MissingChild::withoutEvents(function () use ($owner, $province, $minutesAgo): MissingChild {
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
                'status' => MissingChild::STATUS_HANDED_OVER,
                'handover_confirmed_at' => $this->subSince($minutesAgo),
            ]);
        });
    }
}
