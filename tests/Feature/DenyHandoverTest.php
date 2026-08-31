<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Việc 2 — Nút "Không phải con tôi" (phủ nhận kết quả khớp) ở bước HANDED_OVER.
 *
 * Phủ hợp:
 *  1. Gia đình bấm "Không phải con tôi" → cascade revert đúng (FaceMatchResult
 *     CONFIRMED → DISMISSED, FoundChild revert về trạng thái trước, MissingChild
 *     quay lại missing_child_prev_status = FOUND), audit log ghi rõ lý do.
 *  2. Bug fix: khối xác nhận (và nút mới) HIỆN THỊ ở status HANDED_OVER; ẨN ở REUNITED.
 *  3. Idempotent: đã APPROVED hoặc REUNITED → 409 (không double-revert, không
 *     crash).
 */
class DenyHandoverTest extends TestCase
{
    use RefreshDatabase;

    private function province()
    {
        return \App\Models\Province::firstOrCreate(
            ['code' => '00'],
            ['name' => 'TP. Hồ Chí Minh', 'short_code' => 'HCM', 'region' => 'NAM', 'is_active' => 1]
        );
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

    private function foundCaseWithConfirmedMatch(User $family, User $foundPoster): array
    {
        $case = $this->foundCaseOwnedBy($family);

        $found = FoundChild::withoutEvents(fn () => FoundChild::create([
            'user_id' => $foundPoster->getKey(),
            'province_id' => $this->province()->getKey(),
            'found_date' => now()->format('Y-m-d'),
            'status' => FoundChild::STATUS_MATCHED,
        ]));

        $match = FaceMatchResult::create([
            'missing_id' => $case->getKey(),
            'found_id' => $found->getKey(),
            'status' => 'CONFIRMED',
            'score' => 0.92,
            'confidence_level' => 'HIGH',
            'source' => 'MANUAL',
            'is_matched' => true,
        ]);

        return [$case, $found, $foundPoster, $match];
    }

    // ----------------------------------------------------------------- 1

    public function test_family_deny_handover_triggers_cascade_revert_and_audit(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
        ]);
        $foundPoster = User::factory()->create(['role' => 'USER', 'kyc_status' => 'VERIFIED']);

        [$case, $found, $foundPoster, $match] = $this->foundCaseWithConfirmedMatch($family, $foundPoster);

        // Đưa lên HANDED_OVER (bước trước khi phủ nhận). Snapshot prev = FOUND.
        $this->actingAs($family)
            ->post(route('missing.confirm-handover', $case))
            ->assertRedirect();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->status);
        $this->assertSame(MissingChild::STATUS_FOUND, $case->missing_child_prev_status);

        // Gia đình phủ nhận.
        $this->actingAs($family)
            ->post(route('missing.deny-handover', $case))
            ->assertRedirect(route('missing.detail', ['id' => $case->id]));

        $case->refresh();
        $match->refresh();
        $found->refresh();

        // MissingChild quay lại FOUND (prev status), clear cờ handover.
        $this->assertSame(MissingChild::STATUS_FOUND, $case->status);
        $this->assertNull($case->handover_confirmed_at);
        $this->assertNull($case->handover_confirmed_by);
        $this->assertNull($case->handover_point_id);

        // FaceMatchResult CONFIRMED → DISMISSED.
        $this->assertSame('DISMISSED', $match->status);

        // FoundChild revert về APPROVED (trước khi MATCHED).
        $this->assertSame(FoundChild::STATUS_APPROVED, $found->status);

        // Audit log ghi rõ lý do "Gia đình phủ nhận kết quả khớp #<id>".
        // Dùng new_value->status để lấy đúng AuditLog của bước phủ nhận (FOUND revert),
        // vì SQLite timestamp không có microseconds nên 2 bản ghi cùng giây → latest() không ổn định.
        $log = AuditLog::query()
            ->where('target_type', $case->getTable())
            ->where('target_id', $case->getKey())
            ->where('action', 'missing_children.status_updated')
            ->where('new_value->status', MissingChild::STATUS_FOUND)
            ->first();

        $this->assertNotNull($log, 'Phải có 1 AuditLog khi gia đình phủ nhận.');
        $this->assertStringContainsString('Gia đình phủ nhận kết quả khớp #' . $match->getKey(), $log->reason);
    }

    // ----------------------------------------------------------------- 2

    public function test_deny_button_visible_at_handed_over_hidden_at_reunited(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
        ]);
        $foundPoster = User::factory()->create(['role' => 'USER', 'kyc_status' => 'VERIFIED']);

        [$case, $found, $foundPoster] = $this->foundCaseWithConfirmedMatch($family, $foundPoster);

        // Lên HANDED_OVER → nút "Không phải con tôi" phải hiện ở trang chi tiết.
        $this->actingAs($family)
            ->post(route('missing.confirm-handover', $case))
            ->assertRedirect();

        $this->actingAs($family)
            ->get(route('missing.detail', ['id' => $case->id]))
            ->assertSee('Không phải con tôi')
            ->assertSee('Xác nhận bé đã được bàn giao'); // nút cũ vẫn hiện, không bị xoá

        // Chuyển sang REUNITED → cả 2 nút phải ẩn (không còn ở bước xác nhận bàn giao).
        $case->forceFill([
            'status' => MissingChild::STATUS_REUNITED,
            'reunited_confirmed_at' => now(),
            'reunited_confirmed_by' => $family->getKey(),
            'reunited_verification_method' => 'FAMILY_SELF',
        ])->save();

        $this->actingAs($family)
            ->get(route('missing.detail', ['id' => $case->id]))
            ->assertDontSee('Không phải con tôi')
            ->assertDontSee('Xác nhận bé đã được bàn giao');
    }

    // ----------------------------------------------------------------- 3

    public function test_deny_is_idempotent_for_approved_and_reunited(): void
    {
        $family = User::factory()->create([
            'role' => 'USER',
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
        ]);

        // Đã APPROVED (chưa bao giờ HANDED_OVER) → 409.
        $approved = MissingChild::withoutEvents(fn () => MissingChild::query()->forceCreate([
            'user_id' => $family->getKey(),
            'case_code' => 'MC-' . fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'gender' => 'UNKNOWN',
            'province_id' => $this->province()->getKey(),
            'missing_date' => now()->subMonth(),
            'contact_name' => 'Người thân',
            'contact_phone' => '0905123456',
            'status' => MissingChild::STATUS_APPROVED,
        ]));

        $this->actingAs($family)
            ->post(route('missing.deny-handover', $approved))
            ->assertStatus(409);

        // Đã REUNITED → 409 (không revert lại).
        $reunited = MissingChild::withoutEvents(fn () => MissingChild::query()->forceCreate([
            'user_id' => $family->getKey(),
            'case_code' => 'MC-' . fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'gender' => 'UNKNOWN',
            'province_id' => $this->province()->getKey(),
            'missing_date' => now()->subMonth(),
            'contact_name' => 'Người thân',
            'contact_phone' => '0905123456',
            'status' => MissingChild::STATUS_REUNITED,
        ]));

        $this->actingAs($family)
            ->post(route('missing.deny-handover', $reunited))
            ->assertStatus(409);
    }
}
