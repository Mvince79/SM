<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class VolunteerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public static function roleKycMatrix(): array
    {
        $roles = ['ADMIN', 'MODERATOR', 'VOLUNTEER', 'USER'];
        $statuses = ['NONE', 'PENDING', 'VERIFIED', 'REJECTED'];

        $cases = [];
        foreach ($roles as $role) {
            foreach ($statuses as $status) {
                $expectedAllow = $role === 'VOLUNTEER' && $status === 'VERIFIED';
                $cases["{$role}-{$status}"] = [$role, $status, $expectedAllow];
            }
        }

        return $cases;
    }

    /** @dataProvider roleKycMatrix */
    public function test_access_volunteer_features_matrix(string $role, string $kycStatus, bool $expectedAllow): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'kyc_status' => $kycStatus,
            'status' => 'ACTIVE',
        ]);

        $result = Gate::forUser($user)->allows('access-volunteer-features');

        $this->assertSame($expectedAllow, $result, "Role={$role} KYC={$kycStatus} kỳ vọng allow=" . ($expectedAllow ? 'true' : 'false'));
    }

    public function test_banned_volunteer_is_always_denied_even_if_verified(): void
    {
        $user = User::factory()->create([
            'role' => 'VOLUNTEER',
            'kyc_status' => 'VERIFIED',
            'status' => 'BANNED',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('access-volunteer-features'));
    }
}