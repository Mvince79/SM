<?php

namespace Database\Factories;

use App\Models\HandoverPoint;
use App\Models\ThirdPartyVerifierProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThirdPartyVerifierProfile>
 */
class ThirdPartyVerifierProfileFactory extends Factory
{
    protected $model = ThirdPartyVerifierProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'THIRD_PARTY_VERIFIER']),
            'organization_type' => $this->faker->randomElement(['POLICE', 'GOVERNMENT', 'NGO']),
            'organization_name' => $this->faker->company(),
            'badge_or_staff_id' => $this->faker->unique()->bothify('BADGE-####??'),
            'handover_point_id' => HandoverPoint::factory(),
            'verified_at' => now(),
        ];
    }
}