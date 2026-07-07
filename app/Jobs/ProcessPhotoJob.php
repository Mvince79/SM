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

    private function generateDerivatives(Photo $photo): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($photo->path_original)) {
            return;
        }

        $originalData = $disk->get($photo->path_original);
        $srcImage = @imagecreatefromstring($originalData);

        if (! $srcImage) {
            return;
        }

        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);

        $photo->width = $origW;
        $photo->height = $origH;

        $thumbPath = $this->derivePath($photo->path_original, '_thumb');
        $thumb = $this->cropCenter($srcImage, $origW, $origH, 200, 200);
        $disk->put($thumbPath, $this->encodeJpeg($thumb, 85));
        imagedestroy($thumb);
        $photo->path_thumb = $thumbPath;

        $blurPath = $this->derivePath($photo->path_original, '_blurred');
        $blurW = 400;
        $blurH = (int) round($origH * $blurW / max(1, $origW));
        $blurred = imagecreatetruecolor($blurW, $blurH);
        imagecopyresampled($blurred, $srcImage, 0, 0, 0, 0, $blurW, $blurH, $origW, $origH);

        for ($i = 0; $i < 5; $i++) {
            imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $disk->put($blurPath, $this->encodeJpeg($blurred, 70));
        imagedestroy($blurred);
        $photo->path_blurred = $blurPath;

        imagedestroy($srcImage);
        $photo->save();
    }

    private function derivePath(string $originalPath, string $suffix): string
    {
        $ext = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'jpg';
        $noExt = substr($originalPath, 0, -(strlen($ext) + 1));

        return "{$noExt}{$suffix}.jpg";
    }

    private function cropCenter(\GdImage $src, int $srcW, int $srcH, int $dstW, int $dstH): \GdImage
    {
        $scale = max($dstW / max(1, $srcW), $dstH / max(1, $srcH));
        $scaledW = (int) round($srcW * $scale);
        $scaledH = (int) round($srcH * $scale);

        $scaled = imagecreatetruecolor($scaledW, $scaledH);
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $scaledW, $scaledH, $srcW, $srcH);

        $x = (int) round(($scaledW - $dstW) / 2);
        $y = (int) round(($scaledH - $dstH) / 2);

        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopy($dst, $scaled, 0, 0, $x, $y, $dstW, $dstH);
        imagedestroy($scaled);

        return $dst;
    }

    private function encodeJpeg(\GdImage $img, int $quality = 85): string
    {
        ob_start();
        imagejpeg($img, null, $quality);

        return ob_get_clean();
    }
}
