<?php

namespace Tests\Feature;

use App\Events\FaceMatchFound;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use App\Models\User;
use App\Notifications\FaceMatchNotification;
use App\Services\FaceMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class TriggerFaceMatchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_trigger_face_match_job_creates_match_result_and_dispatches_event(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.8735,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'missing_id' => $missingPhoto->model_id,
            'found_id' => $foundPhoto->model_id,
            'score' => 0.8735,
            'confidence_level' => 'HIGH',
            'status' => 'PENDING',
        ]);

        $this->assertNotNull(FaceMatchResult::query()->first()->notified_at);
        Event::assertDispatched(FaceMatchFound::class);
    }

    public function test_matched_face_result_notifies_admins(): void
    {
        Notification::fake();
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);
        $moderator = User::factory()->create([
            'role' => 'MODERATOR',
        ]);
        $normalUser = User::factory()->create([
            'role' => 'USER',
        ]);

        [$missingPhoto] = $this->createPhotoPair();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.8735,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        Notification::assertSentTo($admin, FaceMatchNotification::class);
        Notification::assertSentTo($moderator, FaceMatchNotification::class);
        Notification::assertNotSentTo($normalUser, FaceMatchNotification::class);
    }

    public function test_medium_confidence_match_is_saved_for_admin_review(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.7825,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'score' => 0.7825,
            'is_matched' => 1,
            'confidence_level' => 'MEDIUM',
            'status' => 'PENDING',
        ]);

        Event::assertDispatched(FaceMatchFound::class);
    }

    public function test_reviewed_match_result_is_not_reopened_by_later_ai_runs(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        FaceMatchResult::query()->create([
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'missing_id' => $missingPhoto->model_id,
            'found_id' => $foundPhoto->model_id,
            'score' => 0.9123,
            'is_matched' => true,
            'confidence_level' => 'HIGH',
            'source' => 'REALTIME',
            'model_used' => 'ArcFace',
            'status' => 'DISMISSED',
        ]);

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.9735,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'score' => 0.9123,
            'status' => 'DISMISSED',
        ]);
        Event::assertNotDispatched(FaceMatchFound::class);
    }

    public function test_trigger_face_match_job_compares_faces_regardless_of_detected_gender(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();
        $missingPhoto->forceFill(['detected_gender' => 'MALE'])->save();
        $foundPhoto->forceFill(['detected_gender' => 'FEMALE'])->save();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.88,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'status' => 'PENDING',
        ]);
        Event::assertDispatched(FaceMatchFound::class);
    }

    public function test_failed_trigger_job_notifies_admins(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        $photo = Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => 1,
            'path_original' => 'missing/photo.jpg',
            'has_face' => 1,
        ]));

        (new TriggerFaceMatchJob($photo->id))->failed(new \RuntimeException('AI unavailable'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertSame('failed', $photo->refresh()->ai_status);
    }

    private function createPhotoPair(): array
    {
        $user = User::factory()->create();
        $province = Province::factory()->create();

        $missingChild = MissingChild::query()->create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-00001',
            'full_name' => 'Missing Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => 'APPROVED',
        ]);

        $foundChild = FoundChild::query()->create([
            'user_id' => $user->id,
            'province_id' => $province->id,
            'found_date' => now(),
            'gender_estimate' => 'MALE',
            'age_estimate' => 8,
            'status' => 'APPROVED',
        ]);

        $encoding = Crypt::encryptString(json_encode(array_fill(0, 512, 1.0)));

        [$missingPhoto, $foundPhoto] = Photo::withoutEvents(fn () => [
            Photo::query()->create([
                'model_type' => MissingChild::class,
                'model_id' => $missingChild->id,
                'path_original' => 'missing/photo.jpg',
                'has_face' => 1,
                'face_encoding' => $encoding,
            ]),
            Photo::query()->create([
                'model_type' => FoundChild::class,
                'model_id' => $foundChild->id,
                'path_original' => 'found/photo.jpg',
                'has_face' => 1,
                'face_encoding' => $encoding,
            ]),
        ]);

        return [$missingPhoto, $foundPhoto];
    }
}
