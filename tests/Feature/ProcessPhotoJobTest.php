<?php

namespace Tests\Feature;

use App\Exceptions\AIServiceUnavailableException;
use App\Jobs\ProcessPhotoJob;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Photo;
use App\Services\FaceMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessPhotoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_photo_job_detects_face_crop_before_encoding(): void
    {
        Bus::fake([TriggerFaceMatchJob::class]);
        Storage::fake('public');
        Storage::disk('public')->put('photos/original.jpg', $this->jpegImage());

        $photo = Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => 1,
            'path_original' => 'photos/original.jpg',
        ]));

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->once()->andReturnTrue();
        $service->shouldReceive('detectFace')
            ->once()
            ->with('photos/original.jpg')
            ->andReturn([
                'has_face' => true,
                'bounding_box' => ['x' => 10, 'y' => 12, 'w' => 80, 'h' => 80],
                'crop_image_base64' => base64_encode('crop-data'),
                'detected_gender' => 'MALE',
            ]);
        $service->shouldReceive('encode')
            ->once()
            ->with('photos/original.jpg')
            ->andReturn([
                'has_face' => true,
                'encoding' => array_fill(0, 512, 1.0),
                'model' => 'ArcFace',
            ]);

        (new ProcessPhotoJob($photo->id))->handle($service);

        $photo->refresh();

        Storage::disk('public')->assertExists('photos/original_face_crop.jpg');
        $this->assertSame('photos/original_face_crop.jpg', $photo->face_crop_path);
        $this->assertSame(['x' => 10, 'y' => 12, 'w' => 80, 'h' => 80], $photo->face_bounding_box);
        $this->assertSame('MALE', $photo->detected_gender);
        $this->assertSame(1, $photo->has_face);
        $this->assertEquals(array_fill(0, 512, 1.0), json_decode(Crypt::decryptString($photo->face_encoding), true));

        Bus::assertDispatched(TriggerFaceMatchJob::class);
    }

    /**
     * Bước 1 — Mô phỏng AI service "chết" đúng lúc TriggerFaceMatchJob chạy (được
     * dispatch đồng bộ từ ProcessPhotoJob, tức là trong luồng request upload).
     * Yêu cầu: handle() KHÔNG throw (request upload không 500), ảnh được đánh dấu
     * 'pending' để xử lý lại sau, và admin nhận thông báo lỗi.
     */
    public function test_process_photo_job_marks_photo_pending_when_face_match_ai_unavailable(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('photos/original.jpg', $this->jpegImage());
        config(['face_match.sync_retry_delay_ms' => 0]);

        $encoding = Crypt::encryptString(json_encode(array_fill(0, 512, 1.0)));

        $photo = Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => 1,
            'path_original' => 'photos/original.jpg',
            'has_face' => 1,
            'face_encoding' => $encoding,
        ]));

        // Ảnh đích (FoundChild) để vòng lặp compare thực sự được chạy ít nhất 1 lần.
        Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => FoundChild::class,
            'model_id' => 1,
            'path_original' => 'found/photo.jpg',
            'has_face' => 1,
            'face_encoding' => $encoding,
        ]));

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->andReturnTrue();
        $service->shouldReceive('detectFace')
            ->once()
            ->with('photos/original.jpg')
            ->andReturn([
                'has_face' => true,
                'bounding_box' => ['x' => 10, 'y' => 12, 'w' => 80, 'h' => 80],
                'crop_image_base64' => base64_encode('crop-data'),
                'detected_gender' => 'MALE',
            ]);
        $service->shouldReceive('encode')
            ->once()
            ->with('photos/original.jpg')
            ->andReturn([
                'has_face' => true,
                'encoding' => array_fill(0, 512, 1.0),
                'model' => 'ArcFace',
            ]);
        // AI service "chết" đúng lúc compare() được gọi (giữa vòng lặp so khớp).
        // AiCallRetry sẽ gọi lại vài lần (đều thất bại) rồi mới chuyển sang xử lý
        // graceful, nên mock KHÔNG dùng ->once() ở đây.
        $service->shouldReceive('compare')
            ->andThrow(new AIServiceUnavailableException('AI compare request failed with status 503'));

        // Bind vào container để TriggerFaceMatchJob::dispatch (chạy đồng bộ) resolve
        // CÙNG mock này — nếu không nó sẽ lấy một FaceMatchService mới từ container.
        app()->instance(FaceMatchService::class, $service);

        // PHẢI KHÔNG throw — đây là điều giữ request upload không bị 500.
        (new ProcessPhotoJob($photo->id))->handle($service);

        $photo->refresh();
        $this->assertSame('pending', $photo->ai_status);
        $this->assertDatabaseHas('notification_events', [
            'event_type' => NotificationEvent::TYPE_AI_PROCESSING_FAILED,
            'subject_type' => Photo::class,
            'subject_id' => $photo->id,
        ]);
    }

    /**
     * Bước 2 — Retry nội bộ (AiCallRetry) phải vượt qua AI service giật tạm thời:
     * detectFace "chết" đúng 1 lần đầu, thử lại thành công lần 2 → ảnh vẫn được xử lý
     * bình thường (không rơi vào pending, không 500).
     */
    public function test_process_photo_job_retries_transient_ai_failure_then_succeeds(): void
    {
        Bus::fake([TriggerFaceMatchJob::class]);
        config(['face_match.sync_retry_delay_ms' => 0]);

        Storage::fake('public');
        Storage::disk('public')->put('photos/original.jpg', $this->jpegImage());

        $photo = Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => 1,
            'path_original' => 'photos/original.jpg',
        ]));

        $service = Mockery::mock(FaceMatchService::class);
        $service->shouldReceive('isHealthy')->andReturnTrue();
        // detectFace ném lỗi đúng lần gọi đầu, trả kết quả thành công từ lần 2.
        $calls = 0;
        $service->shouldReceive('detectFace')
            ->with('photos/original.jpg')
            ->andReturnUsing(function () use (&$calls) {
                $calls++;
                if ($calls === 1) {
                    throw new AIServiceUnavailableException('AI transient down');
                }

                return [
                    'has_face' => true,
                    'bounding_box' => ['x' => 10, 'y' => 12, 'w' => 80, 'h' => 80],
                    'crop_image_base64' => base64_encode('crop-data'),
                    'detected_gender' => 'MALE',
                ];
            });
        $service->shouldReceive('encode')
            ->once()
            ->with('photos/original.jpg')
            ->andReturn([
                'has_face' => true,
                'encoding' => array_fill(0, 512, 1.0),
                'model' => 'ArcFace',
            ]);

        (new ProcessPhotoJob($photo->id))->handle($service);

        $photo->refresh();
        // Nhờ retry, xử lý thành công: có mặt, có encoding, không rơi vào pending.
        $this->assertSame(1, $photo->has_face);
        $this->assertNotNull($photo->face_encoding);
        $this->assertNotSame('pending', $photo->ai_status);
    }

    private function jpegImage(): string
    {
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagejpeg($image);
        imagedestroy($image);

        return ob_get_clean();
    }
}
