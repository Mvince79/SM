<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\EkycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'ocr_raw_response' => ['full_name' => 'Nguyen Van Khang', 'dob' => '2000-01-01'],
            'id_type' => 'CCCD_12',
            'face_match_score' => 0.91,
            'name_matches' => true,
            'id_number_matches' => true,
            'auto_verifiable' => true,
        ]);
        $this->app->instance(EkycService::class, $mock);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $user->refresh();

        $this->assertSame('VERIFIED', $kyc->status);
        $this->assertSame('STANDARD', $kyc->review_level);
        $this->assertSame('CCCD_12', $kyc->id_type);
        $this->assertSame('Nguyen Van Khang', $kyc->full_name_on_id);
        $this->assertSame('2000-01-01', $kyc->dob_on_id->format('Y-m-d'));
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
            'id_type' => null,
            'face_match_score' => 0.42,
            'name_matches' => false,
            'id_number_matches' => false,
            'auto_verifiable' => false,
        ]);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $user->refresh();

        $this->assertSame('PENDING', $kyc->status);
        $this->assertSame('ENHANCED', $kyc->review_level);
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
            'id_type' => 'CCCD_12',
            'face_match_score' => 0.5,
            'name_matches' => false,
            'id_number_matches' => true,
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

    public function test_notifies_admins_when_kyc_needs_review(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create(['kyc_status' => 'PENDING']);
        $kyc = KycVerification::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);

        $mock = Mockery::mock(EkycService::class);
        $mock->shouldReceive('verify')->once()->andReturn([
            'ocr_provider' => 'tesseract',
            'ocr_raw_response' => ['full_name' => 'Nguyen Van A'],
            'id_type' => null,
            'face_match_score' => 0.42,
            'name_matches' => false,
            'id_number_matches' => false,
            'auto_verifiable' => false,
        ]);
        $this->app->instance(EkycService::class, $mock);

        (new ProcessKycJob($kyc->id))->handle($mock);

        $kyc->refresh();
        $this->assertSame('PENDING', $kyc->status);

        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_KYC_REVIEW_REQUIRED,
            'subject_type' => KycVerification::class,
            'subject_id' => $kyc->id,
        ]);

        $event = NotificationEvent::query()
            ->where('event_type', NotificationEvent::TYPE_KYC_REVIEW_REQUIRED)
            ->where('subject_id', $kyc->id)
            ->firstOrFail();

        $this->assertTrue($event->recipients()->where('user_id', $admin->id)->exists());
    }
}
