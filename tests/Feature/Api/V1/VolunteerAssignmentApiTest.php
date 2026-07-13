<?php

namespace Tests\Feature\Api\V1;

use App\Models\Clue;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_volunteer_can_see_nearby_unassigned_clues(): void
    {
        $volunteer = User::factory()->create([
            'role' => 'VOLUNTEER',
            'kyc_status' => 'VERIFIED',
            'status' => 'ACTIVE',
        ]);

        $near = $this->createClue(['lat' => 10.7769000, 'lng' => 106.7009000]);
        $far = $this->createClue(['lat' => 21.0278000, 'lng' => 105.8342000]);
        $verified = $this->createClue(['lat' => 10.7770000, 'lng' => 106.7010000, 'is_verified' => true]);

        $response = $this->actingAs($volunteer, 'sanctum')
            ->getJson('/api/v1/volunteer/nearby-clues?lat=10.7769&lng=106.7009&radius=5');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($near->id, $ids);
        $this->assertNotContains($far->id, $ids);
        $this->assertNotContains($verified->id, $ids);
    }

    public function test_clue_cannot_be_assigned_to_two_volunteers_at_the_same_time(): void
    {
        $firstVolunteer = User::factory()->create([
            'role' => 'VOLUNTEER',
            'kyc_status' => 'VERIFIED',
            'status' => 'ACTIVE',
        ]);
        $secondVolunteer = User::factory()->create([
            'role' => 'VOLUNTEER',
            'kyc_status' => 'VERIFIED',
            'status' => 'ACTIVE',
        ]);
        $clue = $this->createClue();

        $this->actingAs($firstVolunteer, 'sanctum')
            ->postJson('/api/v1/volunteer/assignments', ['clue_id' => $clue->id])
            ->assertCreated();

        $this->actingAs($secondVolunteer, 'sanctum')
            ->postJson('/api/v1/volunteer/assignments', ['clue_id' => $clue->id])
            ->assertStatus(409);

        $this->assertSame(1, VolunteerAssignment::where('clue_id', $clue->id)->count());
    }

    public function test_verified_assignment_updates_clue_verification_fields(): void
    {
        $volunteer = User::factory()->create([
            'role' => 'VOLUNTEER',
            'kyc_status' => 'VERIFIED',
            'status' => 'ACTIVE',
        ]);
        $clue = $this->createClue();
        $assignment = VolunteerAssignment::create([
            'user_id' => $volunteer->id,
            'clue_id' => $clue->id,
            'status' => 'ASSIGNED',
            'assigned_at' => now(),
        ]);

        $this->actingAs($volunteer, 'sanctum')
            ->patchJson("/api/v1/volunteer/assignments/{$assignment->id}", [
                'status' => 'VERIFIED',
                'notes' => 'Da den dia diem va xac minh dung.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'VERIFIED');

        $clue->refresh();
        $this->assertTrue((bool) $clue->is_verified);
        $this->assertSame($volunteer->id, $clue->verified_by);
        $this->assertNotNull($clue->verified_at);
    }

    private function createClue(array $overrides = []): Clue
    {
        $province = Province::create([
            'name' => 'Tinh ' . fake()->unique()->numerify('###'),
            'code' => 'VN-' . fake()->unique()->numerify('###'),
            'short_code' => fake()->unique()->bothify('??#'),
            'region' => 'NAM',
            'is_active' => 1,
        ]);
        $owner = User::factory()->create(['kyc_status' => 'VERIFIED']);
        $missing = MissingChild::create([
            'user_id' => $owner->id,
            'case_code' => 'MC-' . fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'gender' => 'UNKNOWN',
            'province_id' => $province->id,
            'missing_date' => now()->subDay()->toDateString(),
            'contact_name' => 'Nguoi than',
            'contact_phone' => '0905123456',
            'contact_relation' => 'PARENT',
            'status' => 'APPROVED',
        ]);

        return Clue::create(array_merge([
            'missing_id' => $missing->id,
            'user_id' => $owner->id,
            'clue_type' => 'OTHER',
            'description' => 'Nhin thay mot em be o gan khu vuc nay.',
            'lat' => 10.7769000,
            'lng' => 106.7009000,
            'gps_shared_at' => now(),
            'is_verified' => false,
            'is_anonymous' => false,
        ], $overrides));
    }
}
