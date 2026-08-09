<?php

namespace Tests\Feature;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * D-01 — Feature Test: luồng KYC (CMND 9 số / CCCD 12 số).
 *
 * GHI CHÚ: rule validate ở đây dựa theo mô tả Sprint 3.5/4 (Nhóm K):
 *   id_type: ENUM('CMND_9','CCCD_12')
 *   id_number: regex ^\d{9}$ (CMND_9) hoặc ^\d{12}$ (CCCD_12)
 *   full_name_on_id, dob_on_id, front_image, back_image, selfie: bắt buộc
 * Nếu SubmitKycRequest thật có field/rule khác, chỉnh lại phần setUp()/ dataProvider bên dưới.
 */
class KycTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'id_type' => 'CCCD_12',
            'id_number' => '079203012345',
            'full_name_on_id' => 'Nguyen Van A',
            'dob_on_id' => '2003-05-20',
            'front_image' => UploadedFile::fake()->image('front.jpg'),
            'back_image' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ], $overrides);
    }

    public function test_user_can_submit_kyc_with_valid_cccd_12(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id', 'status']])
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'id_type' => 'CCCD_12',
            'status' => 'PENDING',
        ]);

        $this->assertSame('PENDING', $user->fresh()->kyc_status);
    }

    public function test_user_can_submit_kyc_with_valid_cmnd_9(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload([
            'id_type' => 'CMND_9',
            'id_number' => '123456789',
        ]));

        $response->assertStatus(201);

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'id_type' => 'CMND_9',
        ]);
    }

    /**
     * @dataProvider invalidIdNumberProvider
     */
    public function test_kyc_submission_rejects_invalid_id_number_format(string $idType, string $idNumber): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload([
            'id_type' => $idType,
            'id_number' => $idNumber,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_number']);
    }

    public static function invalidIdNumberProvider(): array
    {
        return [
            'CMND_9 with 8 digits' => ['CMND_9', '1234567'],
            'CMND_9 with letters' => ['CMND_9', '12345678a'],
            'CCCD_12 with 11 digits' => ['CCCD_12', '1234567890'],
            'CCCD_12 with 13 digits' => ['CCCD_12', '1234567890123'],
        ];
    }

    public function test_kyc_submission_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_user_cannot_submit_kyc_twice_while_pending(): void
    {
        Queue::fake();
        $user = User::factory()->create(['kyc_status' => 'PENDING']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $response->assertStatus(409);
        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_user_cannot_submit_kyc_when_already_verified(): void
    {
        Queue::fake();
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $response->assertStatus(409);
    }

    public function test_kyc_submission_dispatches_process_kyc_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        Queue::assertPushed(ProcessKycJob::class);
    }

    public function test_kyc_images_are_stored_on_private_disk_not_public(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $kyc = KycVerification::first();

        Storage::disk('private')->assertExists($kyc->front_image_path);
        Storage::disk('private')->assertExists($kyc->back_image_path);
        Storage::disk('private')->assertExists($kyc->selfie_path);
    }

    public function test_id_number_is_encrypted_at_rest_in_database(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/kyc/submit', $this->validPayload([
            'id_number' => '079203012345',
        ]));

        $rawValue = \DB::table('kyc_verifications')->value('id_number');

        $this->assertNotSame('079203012345', $rawValue);

        $kyc = KycVerification::first();
        $this->assertSame('079203012345', $kyc->decrypted_id_number);
    }

    public function test_id_number_never_appears_in_kyc_model_json_output(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/kyc/submit', $this->validPayload());

        $kyc = KycVerification::first();

        $this->assertArrayNotHasKey('id_number', $kyc->toArray());
        $this->assertStringNotContainsString('id_number', $kyc->toJson());
    }

    /**
     * DoD Sprint 5 / D-01: API không được lộ contact_phone / id_number ở endpoint
     * public liên quan tới MissingChild. Kiểm tra chéo tại đây để đảm bảo an toàn
     * dữ liệu nhạy cảm xuyên suốt cả 2 domain (KYC + MissingChild).
     */
    public function test_public_missing_child_api_does_not_leak_contact_phone(): void
    {
        $child = MissingChild::factory()->create([
            'status' => 'APPROVED',
            'contact_phone' => '0987654321',
        ]);

        $response = $this->getJson("/api/v1/missing-children/{$child->id}");

        $response->assertStatus(200);
        $response->assertJsonMissing(['contact_phone' => '0987654321']);
        $this->assertStringNotContainsString('0987654321', $response->getContent());
    }
}