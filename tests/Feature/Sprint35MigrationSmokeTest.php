<?php

namespace Tests\Feature;

use App\Models\IdempotencyKey;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Sprint35MigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('kyc_verifications'));
        $this->assertTrue(Schema::hasTable('user_consents'));
        $this->assertTrue(Schema::hasTable('idempotency_keys'));
        $this->assertTrue(Schema::hasTable('volunteer_assignments'));

        $this->assertTrue(Schema::hasColumn('users', 'kyc_status'));
        $this->assertTrue(Schema::hasColumn('missing_children', 'review_level'));
        $this->assertTrue(Schema::hasColumn('photos', 'face_bounding_box'));
        $this->assertTrue(Schema::hasColumn('photos', 'face_crop_path'));
        $this->assertTrue(Schema::hasColumn('photos', 'detected_gender'));
    }

    public function test_id_number_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_number' => '079099001234',
            'front_image_path' => 'kyc/front.jpg',
            'back_image_path' => 'kyc/back.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
            'status' => 'PENDING',
        ]);

        $raw = \DB::table('kyc_verifications')->where('id', $kyc->id)->value('id_number');

        $this->assertNotSame('079099001234', $raw);
        $this->assertSame('079099001234', Crypt::decryptString($raw));
        $this->assertSame('079099001234', $kyc->fresh()->decrypted_id_number);
    }

    public function test_idempotency_key_is_unique(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();

        IdempotencyKey::create([
            'request_key' => $key,
            'resource_type' => 'missing_children',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        IdempotencyKey::create([
            'request_key' => $key,
            'resource_type' => 'missing_children',
        ]);
    }

    public function test_volunteer_role_is_accepted_and_kyc_status_defaults_to_none(): void
    {
        $user = User::factory()->create(['role' => 'VOLUNTEER']);

        $this->assertSame('VOLUNTEER', $user->fresh()->role);
        $this->assertSame('NONE', $user->fresh()->kyc_status);
    }
}
