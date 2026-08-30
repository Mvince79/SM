<?php

namespace Tests\Feature;

use App\Console\Commands\RemindFamilyReunionConfirmation;
use App\Models\AuditLog;
use App\Models\EscalationQueue;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * H-04 — Nhắc gia đình xác nhận đoàn tụ / leo thang admin / xác nhận hộ.
 *
 * Mô hình "theo mốc" (config notification.reminder_milestones = [1,3,5]):
 *  - Mỗi mốc D+1/3/5 là 1 lần liên hệ, gửi đúng 1 thông báo (không lặp).
 *  - Quá escalate_after_days (5) → leo thang admin (1 lần).
 *  - Quá close_after_days (7) VÀ đã từng nhắc → UNREACHABLE_FAMILY.
 *
 * Bao phủ 4 tiêu chí DoD:
 *  1. HANDED_OVER quá mốc → notification_events nhắc; quá escalate → escalation_queue.
 *  2. Admin xác nhận hộ bắt buộc lý do >= 20 ký tự.
 *  3. Sau khi admin xác nhận → REUNITED + ADMIN_PHONE_CALL + audit_logs (ADMIN + lý do).
 *  4. Đủ 3 mốc liên hệ + quá close_after_days không phản hồi → UNREACHABLE_FAMILY.
 */
class ReunionReminderTest extends TestCase
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

    private function unit(): string
    {
        return config('notification.reminder_time_unit', 'days');
    }

    private function subSince(int $ago): Carbon
    {
        return now()->{"sub{$this->unit()}"}($ago);
    }

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

    private function reachedCount(int $elapsed): int
    {
        return count(array_filter($this->milestones(), fn (int $m) => $m <= $elapsed));
    }

    private function expectedStatus(int $elapsed): string
    {
        return ($elapsed >= $this->closeAfter() && $this->reachedCount($elapsed) >= 1)
            ? MissingChild::STATUS_UNREACHABLE_FAMILY
            : MissingChild::STATUS_HANDED_OVER;
    }

    // ---------------------------------------------------------------- DoD 1

    public function test_case_handed_over_gets_milestone_reminders(): void
    {
        $elapsed = $this->closeAfter() - 1; // chưa tới close → còn HANDED_OVER
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case));
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $case->user_id]);

        if ($this->reachedCount($elapsed) >= 1) {
            $this->assertNotNull($case->fresh()->reunion_reminder_sent_at);
        }
        $this->assertSame($this->expectedStatus($elapsed), $case->fresh()->status);
    }

    public function test_no_reminder_before_first_milestone(): void
    {
        $case = $this->createHandedOverCase(0);

        Artisan::call('reunion:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    public function test_reminders_are_not_duplicated_across_runs(): void
    {
        $elapsed = $this->closeAfter() - 1;
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');
        Artisan::call('reunion:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case));
    }

    public function test_case_handed_over_past_escalate_after_days_is_pushed_to_admin_escalation_queue(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $elapsed = $this->escalateAfter() + 1; // >= escalate, < close → leo thang
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);

        $escalation = EscalationQueue::query()
            ->with('notificationRecipient')
            ->first();

        $this->assertNotNull($escalation, 'Hồ sơ quá escalate_after_days phải được đẩy vào escalation_queue.');
        $this->assertSame(EscalationQueue::STATUS_PENDING, $escalation->status);
        $this->assertSame($admin->getKey(), $escalation->notificationRecipient->user_id);
    }

    public function test_escalation_is_dispatched_only_once_per_case(): void
    {
        User::factory()->create(['role' => 'ADMIN']);
        $elapsed = $this->escalateAfter() + 1;
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');
        Artisan::call('reunion:remind-family');

        $this->assertSame(1, NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION)
            ->where('subject_id', $case->getKey())
            ->count());
        $this->assertSame(1, EscalationQueue::query()->count());
    }

    public function test_not_closed_before_close_after_days_even_if_reminded(): void
    {
        $elapsed = $this->closeAfter() - 1;
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');

        $this->assertSame($this->reachedCount($elapsed), $this->reminderCount($case));
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);
    }

    // ---------------------------------------------------------------- DoD 4

    public function test_all_milestones_then_close_after_close_after_days(): void
    {
        $maxM = $this->maxMilestone();
        $case = $this->createHandedOverCase($maxM);

        // elapsed = maxM: mọi mốc đến hạn, chưa tới close → HANDED_OVER.
        Artisan::call('reunion:remind-family');
        $this->assertSame($this->reachedCount($maxM), $this->reminderCount($case));
        $this->assertSame($this->expectedStatus($maxM), $case->fresh()->status);

        // Tiến tới closeAfter - 1: vẫn HANDED_OVER, số nhắc không đổi (idempotent).
        $this->advance($this->closeAfter() - 1 - $maxM);
        Artisan::call('reunion:remind-family');
        $this->assertSame($this->reachedCount($this->closeAfter() - 1), $this->reminderCount($case));
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);

        // Tiến qua closeAfter: đã nhắc + quá hạn → UNREACHABLE_FAMILY.
        $this->advance(1);
        Artisan::call('reunion:remind-family');
        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $case->fresh()->status);
    }

    public function test_unreachable_transition_is_logged_as_system_actor_without_reason(): void
    {
        $elapsed = $this->closeAfter() + 2;
        $case = $this->createHandedOverCase($elapsed);

        Artisan::call('reunion:remind-family');

        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $case->fresh()->status);

        $log = AuditLog::query()->where('target_id', $case->getKey())->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame('SYSTEM', $log->actor_role);
        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $log->new_value['status']);
    }

    public function test_family_confirmation_stops_the_reminder_loop(): void
    {
        $case = $this->createHandedOverCase($this->closeAfter() - 1);
        $case->forceFill(['status' => MissingChild::STATUS_REUNITED])->saveQuietly();

        Artisan::call('reunion:remind-family');

        $this->assertSame(0, $this->reminderCount($case));
    }

    // ------------------------------------------------------------- DoD 2 & 3

    public function test_admin_confirmation_requires_a_reason_of_at_least_twenty_characters(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $case = $this->createHandedOverCase(daysAgo: 8);

        $this->actingAs($admin);

        foreach (['', '   ', 'Đã gọi điện thoại'] as $tooShort) {
            try {
                $case->confirmReunionByAdmin($tooShort, $admin->getKey());
                $this->fail('Lý do dưới 20 ký tự phải bị chặn: "' . $tooShort . '"');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('reason', $e->errors());
            }

            // Quan trọng: trạng thái KHÔNG được đổi khi lý do bị từ chối.
            $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);
            $this->assertNull($case->fresh()->reunited_confirmed_at);
            $this->assertDatabaseMissing('audit_logs', [
                'target_id' => $case->getKey(),
                'actor_role' => 'ADMIN',
            ]);
        }
    }

    public function test_admin_confirmation_sets_reunited_status_and_writes_admin_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $case = $this->createHandedOverCase(daysAgo: 8);
        $reason = 'Đã gọi điện cho mẹ cháu số 0905123456, gia đình xác nhận đã nhận lại con hôm qua.';

        $this->actingAs($admin);

        $case->confirmReunionByAdmin($reason, $admin->getKey());

        $case->refresh();

        $this->assertSame(MissingChild::STATUS_REUNITED, $case->status);
        $this->assertSame(MissingChild::REUNION_METHOD_ADMIN_PHONE_CALL, $case->reunited_verification_method);
        $this->assertSame($admin->getKey(), $case->reunited_confirmed_by);
        $this->assertNotNull($case->reunited_confirmed_at);

        $log = AuditLog::query()->where('target_id', $case->getKey())->latest('created_at')->first();

        $this->assertNotNull($log, 'Phải ghi audit_logs khi admin xác nhận đoàn tụ hộ.');
        $this->assertSame('ADMIN', $log->actor_role);
        $this->assertSame($admin->getKey(), $log->actor_id);
        $this->assertSame('missing_children.status_updated', $log->action);
        $this->assertSame($reason, $log->reason);
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $log->old_value['status']);
        $this->assertSame(MissingChild::STATUS_REUNITED, $log->new_value['status']);
    }

    // ------------------------------------------------------------------ utils

    private function reminderCount(MissingChild $case): int
    {
        return NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_REUNION_FAMILY_REMINDER)
            ->where('subject_type', $case->getMorphClass())
            ->where('subject_id', $case->getKey())
            ->count();
    }

    private function createHandedOverCase(int $daysAgo): MissingChild
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
                'status' => MissingChild::STATUS_HANDED_OVER,
                'handover_confirmed_at' => $this->subSince($daysAgo),
            ]);
        });
    }
}
