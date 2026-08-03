<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycImageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_each_kyc_image_from_private_storage(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $kyc = KycVerification::factory()->create();

        foreach (['front', 'back', 'selfie'] as $side) {
            $path = match ($side) {
                'front' => $kyc->front_image_path,
                'back' => $kyc->back_image_path,
                'selfie' => $kyc->selfie_path,
            };
            Storage::disk('private')->put($path, "{$side}-image");

            $response = $this->actingAs($admin)
                ->get(route('admin.kyc-verifications.image', [$kyc, $side]));

            $response
                ->assertOk()
                ->assertStreamedContent("{$side}-image");
        }
    }

    public function test_regular_user_cannot_view_private_kyc_images(): void
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'USER']);
        $kyc = KycVerification::factory()->create();
        Storage::disk('private')->put($kyc->front_image_path, 'sensitive-image');

        $this->actingAs($user)
            ->get(route('admin.kyc-verifications.image', [$kyc, 'front']))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_private_kyc_images(): void
    {
        $kyc = KycVerification::factory()->create();

        $this->get(route('admin.kyc-verifications.image', [$kyc, 'front']))
            ->assertRedirect();
    }

    public function test_missing_private_kyc_image_returns_not_found(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'MODERATOR']);
        $kyc = KycVerification::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.kyc-verifications.image', [$kyc, 'front']))
            ->assertNotFound();
    }
}
