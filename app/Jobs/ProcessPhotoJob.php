<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPhotoJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $missingChildID,
        public string $photoPath
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Tìm Photo record theo missingChildId + path_original
        $photo = Photo::where('model_id', $this->missingChildID)
            ->where('path_original', $this->photoPath)
            ->first();

        if (!$photo) {
            // Photo record chưa được tạo hoặc đã bị xoá — bỏ qua
            return;
        }

        $disk = Storage::disk('public');

        // 2. Đọc file gốc
        if (!$disk->exists($this->photoPath)) {
            return;
        }

        $originalData = $disk->get($this->photoPath);
        $srcImage = @imagecreatefromstring($originalData);

        if (!$srcImage) {
            // Không decode được (file hỏng) — bỏ qua
            return;
        }

        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);

        // Lưu width/height gốc vào Photo record
        $photo->width = $origW;
        $photo->height = $origH;

        // ── Tạo Thumbnail 200×200 (crop trung tâm) ─────────────────────────
        $thumbPath = $this->derivePath($this->photoPath, '_thumb');
        $thumb = $this->cropCenter($srcImage, $origW, $origH, 200, 200);
        $thumbData = $this->encodeJpeg($thumb, 85);
        imagedestroy($thumb);
        $disk->put($thumbPath, $thumbData);
        $photo->path_thumb = $thumbPath;

        // ── Tạo Blurred Image (resize 400px wide + Gaussian blur × 5) ──────
        $blurPath = $this->derivePath($this->photoPath, '_blurred');
        $blurW = 400;
        $blurH = (int) round($origH * $blurW / $origW);
        $blurred = imagecreatetruecolor($blurW, $blurH);
        imagecopyresampled($blurred, $srcImage, 0, 0, 0, 0, $blurW, $blurH, $origW, $origH);
        // Apply Gaussian blur 5 passes để đủ mờ
        for ($i = 0; $i < 5; $i++) {
            imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $blurData = $this->encodeJpeg($blurred, 70);
        imagedestroy($blurred);
        $disk->put($blurPath, $blurData);
        $photo->path_blurred = $blurPath;

        imagedestroy($srcImage);

        // 3. Save Photo record
        $photo->save();
    }

    /**
     * Tạo path dẫn xuất bằng cách chèn suffix trước extension.
     * "missing/5/photo.jpg" → "missing/5/photo_thumb.jpg"
     */
    private function derivePath(string $originalPath, string $suffix): string
    {
        $ext = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'jpg';
        $noExt = substr($originalPath, 0, -(strlen($ext) + 1));
        return "{$noExt}{$suffix}.{$ext}";
    }

    /**
     * Crop từ trung tâm ảnh ra kích thước $dstW × $dstH.
     */
    private function cropCenter(\GdImage $src, int $srcW, int $srcH, int $dstW, int $dstH): \GdImage
    {
        $scale = max($dstW / $srcW, $dstH / $srcH);
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

    /**
     * Encode GdImage → JPEG binary string.
     */
    private function encodeJpeg(\GdImage $img, int $quality = 85): string
    {
        ob_start();
        imagejpeg($img, null, $quality);
        return ob_get_clean();
    }
}
