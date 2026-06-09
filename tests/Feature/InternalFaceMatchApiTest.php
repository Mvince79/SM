<?php

namespace Tests\Feature;

use App\Jobs\TriggerFaceMatchJob;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InternalFaceMatchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_endpoint_requires_internal_key(): void
    {
        $response = $this->postJson('/api/internal/face-match/trigger', [
            'photo_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_trigger_endpoint_dispatches_face_match_job(): void
    {
        config(['face_match.internal_key' => 'test-key']);
        Bus::fake();

        $photo = $this->createPhoto();

        $response = $this
            ->withHeader('X-Internal-Key', 'test-key')
            ->postJson('/api/internal/face-match/trigger', [
                'photo_id' => $photo->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['job_id'])
            ->assertJson([
                'queued' => true,
                'photo_id' => $photo->id,
            ]);

        Bus::assertDispatched(
            TriggerFaceMatchJob::class,
            fn (TriggerFaceMatchJob $job) => $job->photoId === $photo->id
        );
    }

    private function createPhoto(): Photo
    {
        $user = User::factory()->create();
        $province = Province::factory()->create();

        $missingChild = MissingChild::query()->create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-00001',
            'full_name' => 'Test Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => 'APPROVED',
        ]);

        return Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => $missingChild->id,
            'path_original' => 'missing/test.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }
}
