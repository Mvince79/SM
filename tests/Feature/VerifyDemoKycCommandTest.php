<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\User;
use App\Services\EkycService;
use Database\Seeders\DemoKycSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class VerifyDemoKycCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_only_resets_both_accounts_without_processing(): void
    {
        Storage::fake('private');

        $this->artisan('demo:verify-kyc', ['--prepare-only' => true])
            ->expectsOutputToContain('sẵn sàng demo bằng giao diện')
            ->assertSuccessful();

        $this->assertSame(0, KycVerification::query()->count());
        $this->assertSame(
            2,
            User::query()
                ->whereIn('email', [
                    DemoKycSeeder::CMND_EMAIL,
                    DemoKycSeeder::CCCD_EMAIL,
                ])
                ->where('kyc_status', 'NONE')
                ->count()
        );
    }

    public function test_it_runs_both_demo_profiles_through_the_kyc_job(): void
    {
        Storage::fake('private');

        $this->mock(EkycService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->twice()
                ->andReturnUsing(function (KycVerification $kyc): array {
                    $profile = collect(DemoKycSeeder::profiles())
                        ->firstWhere('id_type', $kyc->id_type);

                    return [
                        'ocr_provider' => 'demo-test',
                        'ocr_raw_response' => [
                            'provider' => 'demo-test',
                            'id_number' => $profile['id_number'],
                            'id_type' => $profile['id_type'],
                            'full_name' => $profile['full_name'],
                            'dob' => $profile['dob'],
                            'document_type' => $profile['id_type'],
                        ],
                        'id_type' => $profile['id_type'],
                        'face_match_score' => 0.98,
                        'name_matches' => true,
                        'id_number_matches' => true,
                        'auto_verifiable' => true,
                    ];
                });
        });

        $this->artisan('demo:verify-kyc')
            ->expectsOutputToContain('CMND_9 PASS')
            ->expectsOutputToContain('CCCD_12 PASS')
            ->expectsOutputToContain('O-06 PASS')
            ->assertSuccessful();

        $this->assertSame(
            2,
            User::query()
                ->whereIn('email', [
                    DemoKycSeeder::CMND_EMAIL,
                    DemoKycSeeder::CCCD_EMAIL,
                ])
                ->where('kyc_status', 'VERIFIED')
                ->count()
        );

        $this->assertSame(
            2,
            KycVerification::query()
                ->where('status', 'VERIFIED')
                ->where('face_match_score', '>=', 0.85)
                ->count()
        );

        foreach (KycVerification::query()->get() as $kyc) {
            $this->assertArrayNotHasKey('id_number', $kyc->ocr_raw_response);
        }
    }
}
