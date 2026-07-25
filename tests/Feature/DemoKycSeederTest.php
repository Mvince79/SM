<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\User;
use Database\Seeders\DemoKycSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoKycSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_two_synthetic_kyc_accounts_and_private_assets(): void
    {
        Storage::fake('private');

        $this->seed(DemoKycSeeder::class);

        $profiles = DemoKycSeeder::profiles();
        $users = User::query()
            ->whereIn('email', collect($profiles)->pluck('email'))
            ->get()
            ->keyBy('email');

        $this->assertCount(2, $users);
        $this->assertSame('NONE', $users[DemoKycSeeder::CMND_EMAIL]->kyc_status);
        $this->assertSame('NONE', $users[DemoKycSeeder::CCCD_EMAIL]->kyc_status);
        $this->assertTrue(
            Hash::check(
                DemoKycSeeder::DEMO_PASSWORD,
                $users[DemoKycSeeder::CMND_EMAIL]->password,
            )
        );

        foreach (array_keys($profiles) as $profileKey) {
            foreach (['front', 'back', 'selfie'] as $side) {
                Storage::disk('private')->assertExists(
                    DemoKycSeeder::privateAssetPath($profileKey, $side)
                );
            }
        }

        $this->assertSame(0, KycVerification::query()->count());
    }

    public function test_it_can_reset_the_demo_without_duplicate_accounts(): void
    {
        Storage::fake('private');
        $this->seed(DemoKycSeeder::class);

        $user = User::query()->where('email', DemoKycSeeder::CMND_EMAIL)->sole();
        $user->forceFill(['kyc_status' => 'VERIFIED'])->save();
        KycVerification::query()->create([
            'user_id' => $user->getKey(),
            'id_type' => 'CMND_9',
            'id_number' => '012345678',
            'front_image_path' => DemoKycSeeder::privateAssetPath('cmnd', 'front'),
            'back_image_path' => DemoKycSeeder::privateAssetPath('cmnd', 'back'),
            'selfie_path' => DemoKycSeeder::privateAssetPath('cmnd', 'selfie'),
            'status' => 'VERIFIED',
        ]);

        $this->seed(DemoKycSeeder::class);

        $this->assertSame(
            2,
            User::query()
                ->whereIn('email', [
                    DemoKycSeeder::CMND_EMAIL,
                    DemoKycSeeder::CCCD_EMAIL,
                ])
                ->count()
        );
        $this->assertSame(0, KycVerification::query()->count());
        $this->assertSame('NONE', $user->fresh()->kyc_status);
    }
}
