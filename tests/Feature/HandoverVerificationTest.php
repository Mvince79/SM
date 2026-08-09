<?php

namespace Tests\Feature;

use App\Models\HandoverPoint;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Province;
use App\Models\ThirdPartyVerifierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D-01 — Feature Test: xác nhận bàn giao qua Third-Party Verifier.
 * Viết khớp trực tiếp theo app/Policies/HandoverConfirmationPolicy.php thật.
 */
class HandoverVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeVerifierWithProfile(int $provinceId): User
    {
        $handoverPoint = HandoverPoint::factory()->create(['province_id' => $provinceId]);

        $verifier = User::factory()->create(['role' => 'THIRD_PARTY_VERIFIER']);

        ThirdPartyVerifierProfile::factory()->create([
            'user_id' => $verifier->id,
            'handover_point_id' => $handoverPoint->id,
        ]);

        return $verifier;
    }

    public function test_verifier_can_confirm_handover_for_approved_case_in_same_province(): void
    {
        $province = Province::factory()->create();
        $verifier = $this->makeVerifierWithProfile($province->id);

        $child = MissingChild::factory()->create([
            'province_id' => $province->id,
            'status' => 'APPROVED',
            'handover_confirmed_at' => null,
        ]);

        $response = $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertRedirect(route('verifier.dashboard'));
        $response->assertSessionHas('success');

        $child->refresh();
        $this->assertNotNull($child->handover_confirmed_at);
        $this->assertSame($verifier->id, $child->handover_confirmed_by);
    }

    public function test_verifier_cannot_confirm_case_not_yet_approved(): void
    {
        $province = Province::factory()->create();
        $verifier = $this->makeVerifierWithProfile($province->id);

        $child = MissingChild::factory()->create([
            'province_id' => $province->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
        $this->assertNull($child->fresh()->handover_confirmed_at);
    }

    public function test_verifier_cannot_confirm_case_already_confirmed(): void
    {
        $province = Province::factory()->create();
        $verifier = $this->makeVerifierWithProfile($province->id);
        $anotherVerifier = $this->makeVerifierWithProfile($province->id);

        $child = MissingChild::factory()->create([
            'province_id' => $province->id,
            'status' => 'APPROVED',
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => $anotherVerifier->id,
        ]);

        $response = $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
    }

    public function test_verifier_cannot_confirm_handover_for_case_in_different_province(): void
    {
        $verifierProvince = Province::factory()->create();
        $caseProvince = Province::factory()->create();
        $verifier = $this->makeVerifierWithProfile($verifierProvince->id);

        $child = MissingChild::factory()->create([
            'province_id' => $caseProvince->id,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
        $this->assertNull($child->fresh()->handover_confirmed_at);
    }

    public function test_verifier_without_assigned_handover_point_cannot_confirm(): void
    {
        $verifier = User::factory()->create(['role' => 'THIRD_PARTY_VERIFIER']);
        // Không tạo ThirdPartyVerifierProfile -> chưa được gán điểm bàn giao nào

        $province = Province::factory()->create();
        $child = MissingChild::factory()->create([
            'province_id' => $province->id,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
    }

    public function test_normal_user_cannot_access_verifier_confirm_route(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        $response = $this->actingAs($user)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_bypass_verifier_only_route(): void
    {
        // Gate::before ưu tiên ADMIN ở các Policy khác KHÔNG áp dụng ở đây (I-03),
        // và middleware role:THIRD_PARTY_VERIFIER chặn cứng trước khi vào Policy.
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        $response = $this->actingAs($admin)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_confirming_handover(): void
    {
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        $response = $this->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $response->assertRedirect();
    }

    public function test_confirming_handover_creates_notification_event_with_correct_type(): void
    {
        $province = Province::factory()->create();
        $verifier = $this->makeVerifierWithProfile($province->id);

        $child = MissingChild::factory()->create([
            'province_id' => $province->id,
            'status' => 'APPROVED',
        ]);

        $this->actingAs($verifier)
            ->post(route('verifier.handover.confirm', ['handoverable' => $child->id, 'type' => 'missing']));

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => $child->getMorphClass(),
            'subject_id' => $child->id,
            'triggered_by_user_id' => $verifier->id,
        ]);
    }
}