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

    // ---------------------------------------------------------- HANDOVER

    public function test_handover_reminder_triggers_by_minutes_when_unit_is_minutes(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $case = $this->createFoundCase(minutesAgo: 4);

        Artisan::call('handover:remind-family');

        // 4 phút đã trôi → mốc D+1, D+3 (phút) đến hạn, D+5 chưa → 2 nhắc.
        $this->assertSame(2, $this->reminderCount($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER));
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);
    }

    public function test_handover_reminder_no_trigger_before_first_minute_milestone(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $case = $this->createFoundCase(minutesAgo: 0);

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case, NotificationEvent::TYPE_HANDOVER_FAMILY_REMINDER));
    }

    // ---------------------------------------------------------- REUNION

    public function test_reunion_reminder_triggers_by_minutes_when_unit_is_minutes(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $case = $this->createHandedOverCase(minutesAgo: 4);

        Artisan::call('reunion:remind-family');

        $this->assertSame(2, $this->reminderCount($case, NotificationEvent::TYPE_REUNION_FAMILY_REMINDER));
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);
    }

    public function test_reunion_reminder_no_trigger_before_first_minute_milestone(): void
    {
        config(['notification.reminder_time_unit' => 'minutes']);

        $case = $this->createHandedOverCase(minutesAgo: 0);

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
                'found_at' => now()->subMinutes($minutesAgo),
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
                'handover_confirmed_at' => now()->subMinutes($minutesAgo),
            ]);
        });
    }
}
