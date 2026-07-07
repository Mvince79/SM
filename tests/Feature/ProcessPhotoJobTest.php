<?php

namespace Tests\Feature;

use App\Jobs\ProcessPhotoJob;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\MissingChild;
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
            ->with('photos/original_face_crop.jpg')
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
