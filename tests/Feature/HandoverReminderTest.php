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
 * Mô hình "theo mốc" (config notification.reminder_milestones = [1,3,5]):
 *  - FOUND quá mốc → notification_events nhắc; quá escalate → escalation_queue.
 *  - Quá close_after_days (7) VÀ đã từng nhắc → UNREACHABLE_FAMILY.
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

    public function test_found_case_gets_milestone_reminders(): void
    {
        $case = $this->createFoundCase(daysAgo: 4);

        Artisan::call('handover:remind-family');

        // D+4 → mốc D+1, D+3 đến hạn (D+5 chưa) → 2 nhắc.
        $this->assertSame(2, $this->reminderCount($case));
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $case->user_id]);
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);
    }

    public function test_no_reminder_before_first_milestone(): void
    {
        $case = $this->createFoundCase(daysAgo: 0);

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    public function test_reminders_are_not_duplicated_across_runs(): void
    {
        $case = $this->createFoundCase(daysAgo: 4);

        Artisan::call('handover:remind-family');
        Artisan::call('handover:remind-family');

        $this->assertSame(2, $this->reminderCount($case));
    }

    public function test_found_past_escalate_after_days_pushed_to_admin_queue(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $case = $this->createFoundCase(daysAgo: 6);

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
        $case = $this->createFoundCase(daysAgo: 6);

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
        $case = $this->createFoundCase(daysAgo: 4);
        $case->forceFill(['status' => MissingChild::STATUS_HANDED_OVER])->saveQuietly();

        Artisan::call('handover:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    public function test_three_milestones_then_close_after_close_after_days(): void
    {
        $case = $this->createFoundCase(daysAgo: 4);

        // D+4: nhắc D+1, D+3 (2 lần liên hệ).
        Artisan::call('handover:remind-family');
        $this->assertSame(2, $this->reminderCount($case));
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);

        // D+6: nhắc D+5 (đủ 3 lần liên hệ). Chưa tới close_after_days (7).
        Carbon::setTestNow(now()->addDays(2));
        Artisan::call('handover:remind-family');
        $this->assertSame(3, $this->reminderCount($case));
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);

        // D+8: đã quá close_after_days (7) và đã từng nhắc → UNREACHABLE_FAMILY.
        Carbon::setTestNow(now()->addDays(2));
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
                'found_at' => now()->subDays($daysAgo),
            ]);
        });
    }
}
