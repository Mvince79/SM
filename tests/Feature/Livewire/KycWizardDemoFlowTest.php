<?php

namespace Tests\Feature\Livewire;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KycWizardDemoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cmnd_and_cccd_demo_profiles_can_complete_all_three_steps(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessKycJob::class]);

        $profiles = [
            [
                'id_type' => 'CMND_9',
                'id_number' => '012345678',
                'email' => 'wizard.cmnd@example.test',
            ],
            [
                'id_type' => 'CCCD_12',
                'id_number' => '079092000001',
                'email' => 'wizard.cccd@example.test',
            ],
        ];

        foreach ($profiles as $profile) {
            $user = User::factory()->create([
                'email' => $profile['email'],
                'kyc_status' => 'NONE',
            ]);

            Livewire::actingAs($user)
                ->test('profile.kyc-wizard')
                ->call('selectIdType', $profile['id_type'])
                ->call('next')
                ->assertSet('step', 2)
                ->set('id_number', $profile['id_number'])
                ->set('front_image', UploadedFile::fake()->image('front.jpg', 900, 600))
                ->set('back_image', UploadedFile::fake()->image('back.jpg', 900, 600))
                ->call('next')
                ->assertHasNoErrors()
                ->assertSet('step', 3)
                ->set('selfie', UploadedFile::fake()->image('selfie.jpg', 720, 960))
                ->set('selfieCapturedByCamera', true)
                ->call('submit')
                ->assertHasNoErrors();

            $kyc = KycVerification::query()
                ->where('user_id', $user->getKey())
                ->sole();

            $this->assertSame($profile['id_type'], $kyc->id_type);
            $this->assertSame($profile['id_number'], $kyc->decrypted_id_number);
            $this->assertSame('PENDING', $kyc->status);
            $this->assertSame('PENDING', $user->fresh()->kyc_status);
        }

        Queue::assertPushed(ProcessKycJob::class, 2);
    }

    public function test_selfie_step_only_offers_direct_camera_capture(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['kyc_status' => 'NONE']);

        Livewire::actingAs($user)
            ->test('profile.kyc-wizard')
            ->call('selectIdType', 'CCCD_12')
            ->call('next')
            ->set('id_number', '079092000001')
            ->set('front_image', UploadedFile::fake()->image('front.jpg', 900, 600))
            ->set('back_image', UploadedFile::fake()->image('back.jpg', 900, 600))
            ->call('next')
            ->assertSet('step', 3)
            ->assertSee('Chụp bằng camera')
            ->assertSee('Bắt buộc chụp trực tiếp bằng camera trước')
            ->assertDontSee('Tải ảnh có sẵn')
            ->assertDontSee('wire:model="selfie"', false);
    }

    public function test_selfie_upload_is_rejected_without_camera_capture_confirmation(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['kyc_status' => 'NONE']);

        Livewire::actingAs($user)
            ->test('profile.kyc-wizard')
            ->call('selectIdType', 'CCCD_12')
            ->call('next')
            ->set('id_number', '079092000001')
            ->set('front_image', UploadedFile::fake()->image('front.jpg', 900, 600))
            ->set('back_image', UploadedFile::fake()->image('back.jpg', 900, 600))
            ->call('next')
            ->set('selfie', UploadedFile::fake()->image('selfie-from-disk.jpg', 720, 960))
            ->call('submit')
            ->assertHasErrors(['selfieCapturedByCamera']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }
}
