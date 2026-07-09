<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\EkycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProcessKycJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_verifies_when_score_and_name_match(): void
    {
        $user = User::factory()->create(['full_name' => 'Nguyen Van Khang', 'kyc_status' => 'PENDING']);
        $kyc = KycVerification::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);

        $mock = Mockery::mock(EkycService::class);
        $mock->shouldReceive('verify')->once()->andReturn([
            'ocr_provider' => 'tesseract',
            'ocr_raw_response' => ['full_name' => 'Nguyen Van Khang', 'dob' => '01/01/2000'],
            'face_match_score' => 0.91,
            'name_matches' => true,
            'auto_verifiable' => true,
        ]);
        $this->app->instance(EkycService::class, $mock);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $user->refresh();

        $this->assertSame('VERIFIED', $kyc->status);
        $this->assertNotNull($kyc->verified_at);
        $this->assertSame('VERIFIED', $user->kyc_status);
    }

    public function test_stays_pending_when_score_below_threshold(): void
    {
        $user = User::factory()->create(['kyc_status' => 'PENDING']);
        $kyc = KycVerification::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);

        $mock = Mockery::mock(EkycService::class);
        $mock->shouldReceive('verify')->once()->andReturn([
            'ocr_provider' => 'tesseract',
            'ocr_raw_response' => ['full_name' => null],
            'face_match_score' => 0.42,
            'name_matches' => false,
            'auto_verifiable' => false,
        ]);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $user->refresh();

        $this->assertSame('PENDING', $kyc->status);
        $this->assertSame('PENDING', $user->kyc_status);
    }

    public function test_id_number_never_logged_or_stored_in_raw_response(): void
    {
        $user = User::factory()->create();
        $kyc = KycVerification::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);

        $mock = Mockery::mock(EkycService::class);
        $mock->shouldReceive('verify')->once()->andReturn([
            'ocr_provider' => 'tesseract',
            'ocr_raw_response' => ['id_number' => '001200012345', 'full_name' => 'A'],
            'face_match_score' => 0.5,
            'name_matches' => false,
            'auto_verifiable' => false,
        ]);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $this->assertArrayNotHasKey('id_number', $kyc->ocr_raw_response);

        $logContent = file_exists(storage_path('logs/laravel.log'))
            ? file_get_contents(storage_path('logs/laravel.log'))
            : '';
        $this->assertStringNotContainsString('001200012345', $logContent);
    }

    public function test_job_config_matches_ai_job_pattern(): void
    {
        $job = new ProcessKycJob(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 180, 600], $job->backoff);
    }
}