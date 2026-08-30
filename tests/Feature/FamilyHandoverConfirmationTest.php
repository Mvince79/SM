<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * H-05 — Gia đình (role người nhà, đã KYC) là người xác nhận "bé đã được bàn giao"
 * (HANDED_OVER) và "bé đã về nhà" (REUNITED). Không cần xác thực email.
 *
 * Phủ:
 *  1. Gia đình KYC, CHƯA xác thực email → vẫn POST được confirm-handover & confirm-reunited.
 *  2. Khi gia đình xác nhận bàn giao → người TÌM THẤY nhận thông báo
 *     TYPE_FOUND_CASE_HANDED_OVER; gia đình (actor) KHÔNG tự nhận HANDOVER_READY.
 *  3. Khi gia đình xác nhận đoàn tụ → người TÌM THẤY nhận TYPE_FOUND_CASE_REUNITED.
 *  4. Card danh sách (case-card) hiển thị "Đã tìm thấy" cho FOUND, KHÔNG "Bé đã về nhà".
 *  5. Admin set HANDED_OVER bắt buộc lý do (>=20 ký tự) và ghi audit ADMIN.
 */
class FamilyHandoverConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function province(): Province
    {
        return Province::query()->first() ?? Province::factory()->create();
    }

    private function foundCaseOwnedBy(User $owner, int $daysAgo = 1): MissingChild
    {
        $province = $this->province();

        return MissingChild::withoutEvents(fn () => MissingChild::query()->forceCreate([
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
        ]));
    }

    /**
     * Tạo hồ sơ FOUND của gia đình + 1 kết quả khớp CONFIRMED tới trẻ tìm thấy của
     * người tìm thấy. Trả về [$case, $foundChild, $foundPoster].
     */
    private function foundCaseWithConfirmedMatch(User $family, User $foundPoster): array
    {
        $case = $this->foundCaseOwnedBy($family);

        $found = FoundChild::withoutEvents(fn () => FoundChild::create([
            'user_id' => $foundPoster->getKey(),
            'province_id' => $this->province()->getKey(),
            'found_date' => now()->format('Y-m-d'),
            'status' => FoundChild::STATUS_MATCHED,
        ]));

        FaceMatchResult::create([
            'missing_id' => $case->getKey(),
            'found_id' => $found->getKey(),
            'status' => 'CONFIRMED',
            'score' => 0.92,
            'confidence_level' => 'HIGH',
            'source' => 'MANUAL',
            'is_matched' => true,
        ]);

        return [$case, $found, $foundPoster];
    }

    // ----------------------------------------------------------------- 1 & 2

    public function test_family_confirms_handover_without_email_verification(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null, // chưa xác thực email
        ]);
        $foundPoster = User::factory()->create(['role' => 'USER', 'kyc_status' => 'VERIFIED']);

        [$case, $found] = $this->foundCaseWithConfirmedMatch($family, $foundPoster);

        $this->actingAs($family)
            ->post(route('missing.confirm-handover', $case))
            ->assertRedirect(route('missing.detail', ['id' => $case->id]));

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->status);
        $this->assertSame($family->getKey(), $case->handover_confirmed_by);
        $this->assertNotNull($case->handover_confirmed_at);
    }

    public function test_family_handover_notifies_found_poster_not_self(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
        ]);
        $foundPoster = User::factory()->create(['role' => 'USER', 'kyc_status' => 'VERIFIED']);

        [$case] = $this->foundCaseWithConfirmedMatch($family, $foundPoster);

        $this->actingAs($family)->post(route('missing.confirm-handover', $case));

        // Người TÌM THẤY nhận thông báo "trẻ đã được bàn giao".
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_FOUND_CASE_HANDED_OVER)->firstOrFail();
        $this->assertContains(
            $foundPoster->getKey(),
            NotificationRecipient::where('notification_event_id', $event->id)->pluck('user_id')->all()
        );

        // Gia đình (là actor) KHÔNG tự nhận HANDOVER_READY.
        $this->assertDatabaseMissing('notification_events', [
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);
    }

    // ----------------------------------------------------------------- 3

    public function test_family_confirms_reunited_without_email_verification(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
        ]);
        $foundPoster = User::factory()->create(['role' => 'USER', 'kyc_status' => 'VERIFIED']);

        [$case] = $this->foundCaseWithConfirmedMatch($family, $foundPoster);
        $case->forceFill([
            'status' => MissingChild::STATUS_HANDED_OVER,
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => $family->getKey(),
        ])->save();

        $this->actingAs($family)
            ->post(route('missing.confirm-reunited', $case))
            ->assertRedirect(route('missing.detail', ['id' => $case->id]));

        $this->assertSame(MissingChild::STATUS_REUNITED, $case->fresh()->status);

        // Người TÌM THẤY nhận thông báo "trẻ đã về với gia đình".
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_FOUND_CASE_REUNITED,
            'subject_type' => $case->getMorphClass(),
            'subject_id' => $case->getKey(),
        ]);
        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_FOUND_CASE_REUNITED)->firstOrFail();
        $this->assertContains(
            $foundPoster->getKey(),
            NotificationRecipient::where('notification_event_id', $event->id)->pluck('user_id')->all()
        );
    }

    // ----------------------------------------------------------------- 4

    public function test_case_card_shows_da_tim_thay_for_found(): void
    {
        $case = $this->foundCaseOwnedBy(User::factory()->create());

        $view = $this->blade('<x-ui.case-card :case="$case" />', ['case' => $case]);

        $view->assertSee('Đã tìm thấy');
        $view->assertDontSee('Bé đã về nhà');
    }

    // ----------------------------------------------------------------- 5

    public function test_admin_mark_handed_over_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $case = $this->foundCaseOwnedBy(User::factory()->create(), daysAgo: 2);

        $this->actingAs($admin);

        // Thiếu lý do → ValidationException, status không đổi.
        try {
            $case->confirmHandoverByAdmin('', $admin->getKey());
            $this->fail('Thiếu lý do khi admin set HANDED_OVER phải bị chặn.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }

        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);

        // Có lý do → thành công + ghi audit ADMIN.
        $reason = 'Đã gọi điện xác minh qua gia đình, công an phường X xác nhận đã bàn giao bé hôm qua.';
        $case->confirmHandoverByAdmin($reason, $admin->getKey());

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->status);

        $log = AuditLog::query()->where('target_id', $case->getKey())->latest('created_at')->first();
        $this->assertNotNull($log);
        $this->assertSame('ADMIN', $log->actor_role);
        $this->assertSame($admin->getKey(), $log->actor_id);
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $log->new_value['status']);
    }
}
