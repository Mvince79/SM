<?php

namespace App\Jobs;

use App\Exceptions\AIServiceUnavailableException;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Services\FaceMatchService;
use App\Support\AIJobFailureNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProcessPhotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $photoId)
    {
        $this->onQueue(config('face_match.queue', 'face-match'));
    }

    public function handle(FaceMatchService $faceMatchService): void
    {
        $photo = Photo::query()->find($this->photoId);

        if (! $photo) {
            return;
        }

        $photo->forceFill(['ai_status' => 'processing'])->save();

        if (! $faceMatchService->isHealthy()) {
            throw new AIServiceUnavailableException('AI service is unavailable.');
        }

        $this->generateDerivatives($photo);

        $detection = $faceMatchService->detectFace($photo->path_original);

        if (! $detection['has_face']) {
            $photo->forceFill([
                'has_face' => 0,
                'detected_gender' => $detection['detected_gender'] ?? 'UNKNOWN',
                'ai_status' => 'done',
            ])->save();

            return;
        }

        $photo->face_bounding_box = $detection['bounding_box'] ?? null;
        $photo->detected_gender = $detection['detected_gender'] ?? 'UNKNOWN';

        if (! empty($detection['crop_image_base64'])) {
            $cropPath = $this->derivePath($photo->path_original, '_face_crop');
            $cropData = base64_decode($detection['crop_image_base64'], true);

            if ($cropData !== false) {
                Storage::disk('public')->put($cropPath, $cropData);
                $photo->face_crop_path = $cropPath;
            }
        }

        $encodingPath = $photo->face_crop_path ?: $photo->path_original;
        $result = $faceMatchService->encode($encodingPath);
        $photo->has_face = $result['has_face'] ? 1 : 0;

        if ($result['encoding']) {
            $photo->face_encoding = Crypt::encryptString(json_encode($result['encoding']));
        }

        $photo->ai_status = 'done';
        $photo->save();

        if ($photo->has_face && $photo->face_encoding && in_array($photo->model_type, [MissingChild::class, FoundChild::class], true)) {
            TriggerFaceMatchJob::dispatch($photo->id);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Photo::query()
            ->whereKey($this->photoId)
            ->update(['ai_status' => 'failed']);

        Log::error('Photo AI processing failed.', [
            'photo_id' => $this->photoId,
            'message' => $exception->getMessage(),
        ]);

        AIJobFailureNotifier::notifyAdmins($this->photoId, static::class, $exception);
    }

    /**
     * Tạo các bản ảnh phái sinh (thumb / display / blurred) bằng Intervention Image.
     *
     * QUAN TRỌNG (C-04): việc resize/compress ở đây chỉ TẠO THÊM file mới, KHÔNG
     * đụng vào path_original. Bước detectFace()/encode() ngay sau đó vẫn luôn đọc
     * từ path_original (hoặc face_crop_path do chính AI service trả về), nên chất
     * lượng nhận diện khuôn mặt không bị ảnh hưởng bởi việc nén ảnh hiển thị.
     */
    private function generateDerivatives(Photo $photo): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($photo->path_original)) {
            return;
        }

        $originalData = $disk->get($photo->path_original);
        $manager = ImageManager::gd();

        try {
            $probe = $manager->read($originalData);
        } catch (\Throwable $e) {
            Log::warning('ProcessPhotoJob: cannot decode image for derivatives', [
                'photo_id' => $photo->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $photo->width = $probe->width();
        $photo->height = $probe->height();

        // ── 1) Thumbnail 200x200 (crop-fill) — dùng cho list/card, không load ảnh full ──
        $thumb = $manager->read($originalData)->cover(200, 200);
        $thumbPath = $this->derivePath($photo->path_original, '_thumb', 'jpg');
        $disk->put($thumbPath, (string) $thumb->toJpeg(quality: 85));
        $photo->path_thumb = $thumbPath;

        $thumbWebpPath = $this->derivePath($photo->path_original, '_thumb', 'webp');
        $disk->put($thumbWebpPath, (string) $thumb->toWebp(quality: 85));
        $photo->path_thumb_webp = $thumbWebpPath;

        // ── 2) Display: resize tối đa 1600px chiều dài, quality ~80 ──
        // Đây là bản thay path_original khi hiển thị cho người dùng (trang chi tiết,
        // ảnh lớn trong modal...), giúp giảm dung lượng tải về đáng kể.
        $display = $manager->read($originalData)->scaleDown(width: 1600, height: 1600);

        $displayJpegBinary = (string) $display->toJpeg(quality: 80);
        $displayPath = $this->derivePath($photo->path_original, '_display', 'jpg');
        $disk->put($displayPath, $displayJpegBinary);
        $photo->path_display = $displayPath;
        $photo->file_size_display = strlen($displayJpegBinary);

        $displayWebpPath = $this->derivePath($photo->path_original, '_display', 'webp');
        $disk->put($displayWebpPath, (string) $display->toWebp(quality: 80));
        $photo->path_display_webp = $displayWebpPath;

        // ── 3) Blurred placeholder nhỏ (giữ nguyên hành vi cũ cho user chưa xác thực) ──
        $blurred = $manager->read($originalData)->scale(width: 400)->blur(15);
        $blurPath = $this->derivePath($photo->path_original, '_blurred', 'jpg');
        $disk->put($blurPath, (string) $blurred->toJpeg(quality: 70));
        $photo->path_blurred = $blurPath;

        $photo->save();
    }

    private function derivePath(string $originalPath, string $suffix, string $ext = 'jpg'): string
    {
        $origExt = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'jpg';
        $noExt = substr($originalPath, 0, -(strlen($origExt) + 1));

        return "{$noExt}{$suffix}.{$ext}";
    }
}
