<?php

namespace Tests\Feature;

use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider; 


class KycPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('kycStatusProvider')]
    public function test_missing_child_create_policy_respects_kyc_status(string $kycStatus, bool $shouldAllow): void
    {
        $user = User::factory()->create([
            'kyc_status' => $kycStatus,
            'status' => 'ACTIVE', // not banned
        ]);

        $this->assertSame(
            $shouldAllow,
            $user->can('create', MissingChild::class),
            "kyc_status={$kycStatus} kỳ vọng " . ($shouldAllow ? 'ALLOW' : 'DENY') . " cho MissingChild::create"
        );
    }

    #[DataProvider('kycStatusProvider')] 
    public function test_found_child_create_policy_respects_kyc_status(string $kycStatus, bool $shouldAllow): void
    {
        $user = User::factory()->create([
            'kyc_status' => $kycStatus,
            'email_verified_at' => now(), // isVerified() = true
            'status' => 'ACTIVE',
        ]);

        $this->assertSame(
            $shouldAllow,
            $user->can('create', FoundChild::class),
            "kyc_status={$kycStatus} kỳ vọng " . ($shouldAllow ? 'ALLOW' : 'DENY') . " cho FoundChild::create"
        );
    }

    public static function kycStatusProvider(): array
    {
        return [
            'NONE - deny' => ['NONE', false],
            'PENDING - deny' => ['PENDING', false],
            'VERIFIED - allow' => ['VERIFIED', true],
            'REJECTED - deny' => ['REJECTED', false],
        ];
    }

    public function test_banned_user_is_denied_even_if_kyc_verified(): void
    {
        $user = User::factory()->create([
            'kyc_status' => 'VERIFIED',
            'status' => 'BANNED',
        ]);

        $this->assertFalse($user->can('create', MissingChild::class));
    }

    public function test_unverified_email_user_is_denied_even_if_kyc_verified(): void
    {
        $user = User::factory()->create([
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => null,
            'status' => 'ACTIVE',
        ]);

        $this->assertFalse($user->can('create', FoundChild::class));
    }
}
