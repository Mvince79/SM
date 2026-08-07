<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_gui_cccd_12_so_hop_le_thi_submit_kyc_thanh_cong(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/kyc/submit', [
            'id_type' => 'CCCD_12',
            'id_number' => '079123456789', // đúng 12 số
            'full_name_on_id' => 'Nguyễn Văn A',
            'dob_on_id' => '2000-01-01',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'id_type' => 'CCCD_12',
            'status' => 'PENDING',
        ]);

        $this->assertEquals('PENDING', $user->fresh()->kyc_status);
    }

    public function test_user_gui_cmnd_9_so_hop_le_thi_submit_kyc_thanh_cong(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/kyc/submit', [
            'id_type' => 'CMND_9',
            'id_number' => '123456789', // đúng 9 số
            'full_name_on_id' => 'Nguyễn Văn B',
            'dob_on_id' => '1995-05-05',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(201);
    }

    public function test_cccd_sai_dinh_dang_bi_tu_choi(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();

        // Cố tình gửi 9 số nhưng khai id_type là CCCD_12 (cần đúng 12 số) -> phải bị từ chối
        $response = $this->actingAs($user)->postJson('/api/v1/kyc/submit', [
            'id_type' => 'CCCD_12',
            'id_number' => '123456789',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_number']);
    }

    public function test_user_da_pending_khong_the_gui_kyc_lan_2(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['kyc_status' => 'PENDING']);

        $response = $this->actingAs($user)->postJson('/api/v1/kyc/submit', [
            'id_type' => 'CCCD_12',
            'id_number' => '079123456789',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(409);
    }

    public function test_thieu_anh_selfie_bi_tu_choi(): void
    {
        Storage::fake('private');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/kyc/submit', [
            'id_type' => 'CCCD_12',
            'id_number' => '079123456789',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            // thiếu selfie
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['selfie']);
    }
}