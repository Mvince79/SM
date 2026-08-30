<?php

namespace App\Jobs;

use App\Exceptions\AIServiceUnavailableException;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Services\FaceMatchService;
use App\Support\AIJobFailureNotifier;
use App\Support\AiCallRetry;
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
        // Chạy ĐỒNG BỘ (sync) thay vì qua queue worker: đảm bảo mọi ảnh upload
        // đều được detect + encode ngay lập tức, không bao giờ kẹt chờ worker.
        $this->onConnection('sync');
    }

    public function handle(FaceMatchService $faceMatchService): void
    {
        $photo = Photo::query()->find($this->photoId);

        if (! $photo) {
            return;
        }

        $photo->forceFill(['ai_status' => 'processing'])->save();

        // Tạo ảnh phái sinh (không cần AI) trước, phòng AI tạm ngắt.
        $this->generateDerivatives($photo);

        try {
            // isHealthy là tiền kiểm nhanh; retry vài lần để vượt AI giật tạm thời
            // (Laravel Queue retry vô tác dụng vì job ép sync — xem AiCallRetry).
            AiCallRetry::run(function () use ($faceMatchService) {
                if (! $faceMatchService->isHealthy()) {
                    throw new AIServiceUnavailableException('AI service is unavailable.');
                }
            });

            $detection = AiCallRetry::run(fn () => $faceMatchService->detectFace($photo->path_original));

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

            // LUÔN encode từ ảnh gốc (path_original) để ĐỒNG NHẤT với truy vấn tìm kiếm
            // bằng hình ảnh (PhotoSearchController cũng encode từ ảnh gốc upload). Nếu encode
            // từ face_crop_path, encoding lưu và encoding truy vấn sẽ lệch nguồn → cùng 1 ảnh
            // không khớp 100% (thực tế ~0.78). Ảnh crop vẫn sinh riêng để hiển thị UI.
            $encodingPath = $photo->path_original;
            $result = AiCallRetry::run(fn () => $faceMatchService->encode($encodingPath));
            $photo->has_face = $result['has_face'] ? 1 : 0;

            if ($result['encoding']) {
                $photo->face_encoding = Crypt::encryptString(json_encode($result['encoding']));
            }

            $photo->ai_status = 'done';
            $photo->save();
        } catch (AIServiceUnavailableException $e) {
            // AI tạm ngắt: để ảnh ở 'pending' để xử lý lại sau, KHÔNG ném lỗi làm
            // hỏng request upload của người dùng.
            $photo->forceFill(['ai_status' => 'pending'])->save();
            Log::warning('ProcessPhotoJob skipped (AI unavailable), left pending.', [
                'photo_id' => $photo->id,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        if ($photo->has_face && $photo->face_encoding && in_array($photo->model_type, [MissingChild::class, FoundChild::class], true)) {
            try {
                // Chạy ĐỒNG BỘ (sync) trong luồng request upload. Nếu AI service chập
                // chờn đúng lúc so khớp (AIServiceUnavailableException) hoặc bất kỳ lỗi
                // nào khác, BẮT tại đây để KHÔNG ném lỗi làm hỏng request upload của
                // người dùng — đúng theo thiết kế fail-safe gốc. Ảnh được đánh dấu
                // 'pending' (AI gián đoạn, thử lại sau) hoặc 'failed' (lỗi khác) và admin
                // nhận thông báo qua đường ống hợp nhất.
                TriggerFaceMatchJob::dispatch($photo->id);
            } catch (AIServiceUnavailableException $e) {
                $photo->forceFill(['ai_status' => 'pending'])->save();
                Log::warning('TriggerFaceMatchJob skipped (AI unavailable), left pending.', [
                    'photo_id' => $photo->id,
                    'message' => $e->getMessage(),
                ]);
                $this->notifyAdmin($photo, $e->getMessage());

                return;
            } catch (\Throwable $e) {
                $photo->forceFill(['ai_status' => 'failed'])->save();
                Log::error('TriggerFaceMatchJob failed during upload.', [
                    'photo_id' => $photo->id,
                    'message' => $e->getMessage(),
                ]);
                $this->notifyAdmin($photo, $e->getMessage());

                return;
            }
        }

        // Chỉ đánh dấu 'done' khi luồng so khớp KHÔNG tự đánh dấu ảnh để thử lại sau.
        // (TriggerFaceMatchJob chạy đồng bộ; nếu gặp lỗi AI nó tự set 'pending'/'failed'
        //  và không throw, nên tôn trọng trạng thái đó thay của ghi đè 'done'.)
        $photo->refresh();
        if ($photo->ai_status === 'processing') {
            $photo->forceFill(['ai_status' => 'done'])->save();
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

        $photo = Photo::query()->find($this->photoId);
        if ($photo) {
            $this->notifyAdmin($photo, $exception->getMessage());
        }
    }

    /**
     * Báo lỗi xử lý AI cho admin/moderator qua đường ống hợp nhất (chuông + email
     * theo policy), không làm chết luồng xử lý chính của job.
     */
    private function notifyAdmin(Photo $photo, string $message): void
    {
        AIJobFailureNotifier::notifyAdmins($photo, static::class, $message);
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
