<?php

namespace Tests\Feature;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycSubmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_kyc_creates_pending_record_and_updates_user_status(): void
    {
        Storage::fake('private');
        Queue::fake([ProcessKycJob::class]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create(['kyc_status' => 'NONE']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/kyc/submit', [
            'id_number' => '079203001234',
            'full_name_on_id' => $user->full_name ?? 'Nguyen Van A',
            'dob_on_id' => '2000-01-01',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'status' => 'PENDING',
        ]);

        $this->assertSame('PENDING', $user->fresh()->kyc_status);

        $kyc = KycVerification::query()->where('user_id', $user->id)->sole();
        Queue::assertPushed(
            ProcessKycJob::class,
            fn (ProcessKycJob $job): bool => $job->kycVerificationId === $kyc->id
        );

        // id_number không được lưu plaintext trong DB
        // [FIX] đổi \DB:: -> DB:: (dùng use Illuminate\Support\Facades\DB; ở trên)
        // \DB:: trỏ vào namespace gốc, bỏ qua "use" nên sẽ lỗi "Class DB not found" lúc chạy.
        $raw = DB::table('kyc_verifications')->where('user_id', $user->id)->value('id_number');
        $this->assertNotSame('079203001234', $raw);
    }

    public function test_submit_kyc_is_blocked_when_already_pending_or_verified(): void
    {
        Storage::fake('private');

        /** @var \App\Models\User $user */
        $user = User::factory()->create(['kyc_status' => 'PENDING']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/kyc/submit', [
            'id_number' => '079203001234',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(409);
    }

    public function test_submit_kyc_requires_authentication(): void
    {
        Storage::fake('private');

        // [NEW] test bổ sung để khóa lại lỗ hổng đã sửa trong routes/api.php:
        // route /kyc/submit PHẢI nằm sau auth:sanctum, guest gọi phải bị 401.
        $response = $this->postJson('/api/v1/kyc/submit', [
            'id_number' => '079203001234',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(401);
    }

    public function test_id_number_is_encrypted_and_decrypted_via_model(): void
    {
        $user = User::factory()->create();

        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_number' => '079203001234',
            'front_image_path' => 'kyc/1/front.jpg',
            'back_image_path' => 'kyc/1/back.jpg',
            'selfie_path' => 'kyc/1/selfie.jpg',
            'status' => 'PENDING',
        ]);

        // Model thật dùng setIdNumberAttribute() để mã hóa và
        // getDecryptedIdNumberAttribute() để giải mã (không phải cast/accessor
        // trên chính 'id_number').
        $this->assertSame('079203001234', $kyc->decrypted_id_number);
        $this->assertArrayNotHasKey('id_number', $kyc->toArray()); // $hidden
    }
}
