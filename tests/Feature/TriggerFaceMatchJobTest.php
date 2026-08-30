<?php

namespace Tests\Feature;

use App\Events\FaceMatchFound;
use App\Exceptions\AIServiceUnavailableException;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Photo;
use App\Models\Province;
use App\Models\User;
use App\Services\FaceMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
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
        $missingChild = MissingChild::query()->find($missingPhoto->model_id);
        $family = $missingChild->user;

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('compare')->once()->andReturn([
            'score' => 0.8735,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        // FACE_MATCH_SUGGESTED ghi qua đường ống hợp nhất (chuông + email admin
        // theo policy), thay vì FaceMatchNotification hệ cũ.
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_FACE_MATCH_SUGGESTED,
            'subject_type' => FaceMatchResult::class,
            'subject_id' => FaceMatchResult::query()->first()->getKey(),
        ]);

        // Admin + moderator + gia đình (chủ hồ sơ) đều nhận; người dùng vô can thì không.
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $moderator->id]);
        $this->assertDatabaseHas('notification_recipients', ['user_id' => $family->id]);
        $this->assertDatabaseMissing('notification_recipients', ['user_id' => $normalUser->id]);
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

        // Lỗi AI báo cho admin qua đường ống hợp nhất (chuông + email theo policy),
        // không còn ghi vào bảng `notifications` hệ cũ.
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_AI_PROCESSING_FAILED,
            'subject_type' => Photo::class,
            'subject_id' => $photo->id,
        ]);
        $this->assertDatabaseHas('notification_recipients', [
            'user_id' => $admin->id,
        ]);

        $this->assertSame('failed', $photo->refresh()->ai_status);
    }

    /**
     * Bước 2 — Retry nội bộ (AiCallRetry) phải vượt qua AI service giật tạm thời:
     * compare() "chết" đúng 1 lần đầu, thử lại thành công lần 2 → FaceMatchResult
     * vẫn được tạo bình thường (không rơi vào pending / không throw).
     */
    public function test_trigger_face_match_job_retries_transient_ai_failure_then_creates_result(): void
    {
        Event::fake([FaceMatchFound::class]);
        config(['face_match.sync_retry_delay_ms' => 0]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        // compare ném lỗi đúng lần gọi đầu, trả kết quả từ lần 2.
        $calls = 0;
        $service->shouldReceive('compare')
            ->andReturnUsing(function () use (&$calls) {
                $calls++;
                if ($calls === 1) {
                    throw new AIServiceUnavailableException('AI transient down');
                }

                return [
                    'score' => 0.8735,
                    'is_matched' => true,
                    'model' => 'ArcFace',
                ];
            });

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'score' => 0.8735,
            'confidence_level' => 'HIGH',
            'status' => 'PENDING',
        ]);
    }

    /**
     * Bước 4 — Race condition: chạy job 2 lần (mô phỏng 2 upload đồng thời xử lý cùng
     * 1 cặp ảnh) phải tạo ĐÚNG 1 FaceMatchResult và ĐÚNG 1 notify event, không trùng lặp.
     * Unique constraint đã có sẵn nên tiến trình "thua" phải được catch + re-read,
     * không ném lỗi ra ngoài hay sinh bản ghi thứ 2.
     */
    public function test_repeated_runs_do_not_create_duplicate_match_results(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->andReturnTrue();
        $service->shouldReceive('compare')->andReturn([
            'score' => 0.8735,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        // Chạy 2 lần liên tiếp (tương đương 2 request upload song song cho cùng cặp ảnh)
        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);
        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertSame(
            1,
            FaceMatchResult::query()->where([
                'missing_photo_id' => $missingPhoto->id,
                'found_photo_id' => $foundPhoto->id,
            ])->count(),
            'Phải có đúng 1 FaceMatchResult cho mỗi cặp ảnh (không trùng lặp).'
        );

        // Notify phải idempotent: chỉ 1 FACE_MATCH_SUGGESTED event dù chạy 2 lần.
        $this->assertSame(
            1,
            NotificationEvent::query()->where([
                'event_type' => NotificationEvent::TYPE_FACE_MATCH_SUGGESTED,
                'subject_type' => FaceMatchResult::class,
                'subject_id' => FaceMatchResult::query()->first()->getKey(),
            ])->count()
        );
    }

    /**
     * Bước 4 — Khi đã có 1 kết quả PENDING (chưa duyệt), job chạy lại phải CẬP NHẬT
     * cùng dòng (score mới) chứ không chèn dòng mới. Bổ trợ cho test trùng lặp ở trên.
     */
    public function test_existing_pending_result_is_updated_not_duplicated(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$missingPhoto, $foundPhoto] = $this->createPhotoPair();

        // Kết quả cũ PENDING với score thấp đã tồn tại (vd. do AI chạy trước).
        FaceMatchResult::query()->create([
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'missing_id' => $missingPhoto->model_id,
            'found_id' => $foundPhoto->model_id,
            'score' => 0.55,
            'is_matched' => false,
            'confidence_level' => 'LOW',
            'source' => 'REALTIME',
            'model_used' => 'ArcFace',
            'status' => 'PENDING',
        ]);

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->andReturnTrue();
        $service->shouldReceive('compare')->andReturn([
            'score' => 0.91,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        (new TriggerFaceMatchJob($missingPhoto->id))->handle($service);

        $this->assertSame(
            1,
            FaceMatchResult::query()->where([
                'missing_photo_id' => $missingPhoto->id,
                'found_photo_id' => $foundPhoto->id,
            ])->count()
        );

        $this->assertDatabaseHas('face_match_results', [
            'missing_photo_id' => $missingPhoto->id,
            'found_photo_id' => $foundPhoto->id,
            'score' => 0.91,
            'confidence_level' => 'HIGH',
        ]);
    }

    /**
     * Ẩn hồ sơ đã giải quyết (nguồn): nếu ảnh upload thuộc hồ sơ đã đoàn tụ/đóng...,
     * job phải bỏ qua (không gọi isHealthy/compare, không sinh FaceMatchResult).
     */
    public function test_job_skips_when_source_record_is_resolved(): void
    {
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        // Nguồn là MissingChild đã đoàn tụ (REUNITED) → đã giải quyết.
        $resolvedPhoto = $this->createResolvedSourcePhoto();

        $service = Mockery::mock(FaceMatchService::class);
        // Gate đúng → các bước sau KHÔNG được chạy. Nếu sai, test thất bại.
        $service->shouldReceive('isHealthy')->never();
        $service->shouldReceive('compare')->never();

        (new TriggerFaceMatchJob($resolvedPhoto->id))->handle($service);

        $this->assertDatabaseMissing('face_match_results', [
            'missing_photo_id' => $resolvedPhoto->id,
        ]);
    }

    /**
     * Ẩn hồ sơ đã giải quyết khỏi pool quét: FoundChild có kết quả CONFIRMED trỏ đến
     * MissingChild đã REUNITED phải BỊ loại, trong khi FoundChild active vẫn được so
     * khớp bình thường.
     */
    public function test_resolved_found_child_excluded_from_scan_pool(): void
    {
        Event::fake([FaceMatchFound::class]);
        config([
            'face_match.score_threshold' => 0.70,
            'face_match.high_confidence_threshold' => 0.85,
        ]);

        [$sourcePhoto, $activeFoundPhoto] = $this->createPhotoPair();
        $resolvedFoundPhoto = $this->createResolvedFoundPhoto();

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->andReturnTrue();
        $service->shouldReceive('compare')->andReturn([
            'score' => 0.95,
            'is_matched' => true,
            'model' => 'ArcFace',
        ]);

        // Trước khi chạy: resolved found child đã có đúng 1 kết quả CONFIRMED (của helper).
        $this->assertSame(
            1,
            FaceMatchResult::query()->where('found_photo_id', $resolvedFoundPhoto->id)->count()
        );

        (new TriggerFaceMatchJob($sourcePhoto->id))->handle($service);

        // FoundChild đã giải quyết (CONFIRMED → MissingChild REUNITED) BỊ ẨN:
        // job KHÔNG sinh thêm bản ghi mới nào tham chiếu ảnh của nó (số kết quả không đổi).
        $this->assertSame(
            1,
            FaceMatchResult::query()->where('found_photo_id', $resolvedFoundPhoto->id)->count(),
            'Resolved found child must not be re-matched by the scan.'
        );

        // FoundChild active vẫn được so khớp bình thường (có bản ghi mới).
        $this->assertDatabaseHas('face_match_results', [
            'found_photo_id' => $activeFoundPhoto->id,
            'score' => 0.95,
        ]);
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

    /**
     * Tạo ảnh nguồn thuộc một MissingChild đã đoàn tụ (REUNITED) — hồ sơ "đã giải quyết".
     */
    private function createResolvedSourcePhoto(): Photo
    {
        $user = User::factory()->create();
        $province = Province::factory()->create();

        $missingChild = MissingChild::query()->create([
            'user_id' => $user->id,
            'case_code' => 'MC-RESOLVED-001',
            'full_name' => 'Resolved Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => MissingChild::STATUS_REUNITED,
        ]);

        return Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => $missingChild->id,
            'path_original' => 'missing/resolved.jpg',
            'has_face' => 1,
            'face_encoding' => Crypt::encryptString(json_encode(array_fill(0, 512, 1.0))),
        ]));
    }

    /**
     * Tạo ảnh của một FoundChild "đã giải quyết": có FaceMatchResult CONFIRMED trỏ đến
     * một MissingChild đã REUNITED (đã khớp + phụ huynh xác nhận về nhà).
     */
    private function createResolvedFoundPhoto(): Photo
    {
        $user = User::factory()->create();
        $province = Province::factory()->create();

        $reunitedMissing = MissingChild::query()->create([
            'user_id' => $user->id,
            'case_code' => 'MC-REUNITED-001',
            'full_name' => 'Reunited Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => MissingChild::STATUS_REUNITED,
        ]);

        $foundChild = FoundChild::query()->create([
            'user_id' => $user->id,
            'province_id' => $province->id,
            'found_date' => now(),
            'gender_estimate' => 'MALE',
            'age_estimate' => 8,
            'status' => 'APPROVED',
        ]);

        $photo = Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => FoundChild::class,
            'model_id' => $foundChild->id,
            'path_original' => 'found/resolved.jpg',
            'has_face' => 1,
            'face_encoding' => Crypt::encryptString(json_encode(array_fill(0, 512, 1.0))),
        ]));

        FaceMatchResult::query()->create([
            'missing_photo_id' => null,
            'found_photo_id' => $photo->id,
            'missing_id' => $reunitedMissing->id,
            'found_id' => $foundChild->id,
            'score' => 0.98,
            'is_matched' => true,
            'confidence_level' => 'HIGH',
            'source' => 'REALTIME',
            'model_used' => 'ArcFace',
            'status' => 'CONFIRMED',
        ]);

        return $photo;
    }
}
