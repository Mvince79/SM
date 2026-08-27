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
 * Bao phủ 4 tiêu chí DoD:
 *  1. HANDED_OVER > 3 ngày → notification_events nhắc gia đình;
 *     > 7 ngày → escalation_queue cho admin.
 *  2. Admin xác nhận hộ bắt buộc lý do >= 20 ký tự.
 *  3. Sau khi admin xác nhận → REUNITED + ADMIN_PHONE_CALL + audit_logs (ADMIN + lý do).
 *  4. 3 lần liên hệ trong 14 ngày không phản hồi → UNREACHABLE_FAMILY.
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

    // ---------------------------------------------------------------- DoD 1

    public function test_case_handed_over_more_than_three_days_gets_family_reminder_event(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 4);

        Artisan::call('reunion:remind-family');

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_REUNION_FAMILY_REMINDER,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);

        $this->assertDatabaseHas('notification_recipients', [
            'user_id' => $case->user_id,
        ]);

        $this->assertNotNull($case->fresh()->reunion_reminder_sent_at);
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);
    }

    public function test_case_handed_over_less_than_three_days_is_not_reminded(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 2);

        Artisan::call('reunion:remind-family');

        $this->assertDatabaseMissing('notification_events', [
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);
    }

    public function test_reminder_is_not_duplicated_within_the_interval(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 4);

        Artisan::call('reunion:remind-family');
        Artisan::call('reunion:remind-family');

        $this->assertSame(1, $this->reminderCount($case));
    }

    public function test_case_handed_over_more_than_seven_days_is_pushed_to_admin_escalation_queue(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $case = $this->createHandedOverCase(daysAgo: 8);

        Artisan::call('reunion:remind-family');

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);

        $escalation = EscalationQueue::query()
            ->with('notificationRecipient')
            ->first();

        $this->assertNotNull($escalation, 'Hồ sơ quá 7 ngày phải được đẩy vào escalation_queue.');
        $this->assertSame(EscalationQueue::STATUS_PENDING, $escalation->status);
        $this->assertSame($admin->getKey(), $escalation->notificationRecipient->user_id);
    }

    public function test_escalation_is_dispatched_only_once_per_case(): void
    {
        User::factory()->create(['role' => 'ADMIN']);
        $case = $this->createHandedOverCase(daysAgo: 8);

        Artisan::call('reunion:remind-family');
        Artisan::call('reunion:remind-family');

        $this->assertSame(1, NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_REUNION_ADMIN_ESCALATION)
            ->where('subject_id', $case->getKey())
            ->count());
        $this->assertSame(1, EscalationQueue::query()->count());
    }

    // ---------------------------------------------------------------- DoD 4

    public function test_three_contact_attempts_within_fourteen_days_marks_family_unreachable(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 4);

        // Nhắc mỗi 3 ngày: ngày 4, ngày 7, ngày 10 → đủ 3 lần liên hệ.
        Artisan::call('reunion:remind-family');

        Carbon::setTestNow(now()->addDays(3));
        Artisan::call('reunion:remind-family');

        Carbon::setTestNow(now()->addDays(3));
        Artisan::call('reunion:remind-family');

        $this->assertSame(
            RemindFamilyReunionConfirmation::MAX_CONTACT_ATTEMPTS,
            $this->reminderCount($case),
        );
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->fresh()->status);

        // Lần chạy kế tiếp: đã 3 lần liên hệ mà gia đình không phản hồi.
        Carbon::setTestNow(now()->addDay());
        Artisan::call('reunion:remind-family');

        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $case->fresh()->status);
    }

    public function test_unreachable_transition_is_logged_as_system_actor_without_reason(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 20);

        Artisan::call('reunion:remind-family');

        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $case->fresh()->status);

        $log = AuditLog::query()->where('target_id', $case->getKey())->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame('SYSTEM', $log->actor_role);
        $this->assertSame(MissingChild::STATUS_UNREACHABLE_FAMILY, $log->new_value['status']);
    }

    public function test_family_confirmation_stops_the_reminder_loop(): void
    {
        $case = $this->createHandedOverCase(daysAgo: 4);
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
                'handover_confirmed_at' => now()->subDays($daysAgo),
            ]);
        });
    }
}
