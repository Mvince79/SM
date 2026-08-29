<?php

namespace App\Jobs;

use App\Events\FaceMatchFound;
use App\Exceptions\AIServiceUnavailableException;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\Photo;
use App\Services\FaceMatchService;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use App\Support\AIJobFailureNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TriggerFaceMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 180, 600];

    public string $trackingId;

    public function __construct(public int $photoId, ?string $trackingId = null)
    {
        $this->trackingId = $trackingId ?? (string) Str::uuid();
        // Chạy ĐỒNG BỘ (sync) cùng với ProcessPhotoJob để so khớp diễn ra ngay
        // khi ảnh được encode, không cần queue worker.
        $this->onConnection('sync');
    }

    public function handle(FaceMatchService $faceMatchService): void
    {
        // Giải qua container để giữ handle() gọi được trực tiếp trong test mà
        // không cần truyền dependency; production (sync queue) vẫn tự resolve.
        $notifier = app(Notifier::class);
        $recipients = app(RecipientResolver::class);

        $photo = Photo::query()->find($this->photoId);

        if (! $photo || ! $photo->has_face || ! $photo->face_encoding) {
            return;
        }

        $photo->forceFill(['ai_status' => 'processing'])->save();

        if (! $faceMatchService->isHealthy()) {
            throw new AIServiceUnavailableException('AI service is unavailable.');
        }

        $sourceEncoding = $this->decryptEncoding($photo);

        foreach ($this->targetPhotos($photo) as $targetPhoto) {
            $targetEncoding = $this->decryptEncoding($targetPhoto);
            $comparison = $faceMatchService->compare($sourceEncoding, $targetEncoding);

            $missingPhoto = $photo->model_type === MissingChild::class ? $photo : $targetPhoto;
            $foundPhoto = $photo->model_type === FoundChild::class ? $photo : $targetPhoto;
            $score = (float) $comparison['score'];
            $isMatched = $score >= config('face_match.score_threshold');
            $confidenceLevel = FaceMatchResult::confidenceLevelForScore($score);

            $match = FaceMatchResult::query()->firstOrNew([
                'missing_photo_id' => $missingPhoto->id,
                'found_photo_id' => $foundPhoto->id,
            ]);

            $isReviewed = $match->exists && in_array($match->status, ['CONFIRMED', 'DISMISSED'], true);

            if (! $isReviewed) {
                $match->fill([
                    'missing_id' => $missingPhoto->model_id,
                    'found_id' => $foundPhoto->model_id,
                    'score' => $score,
                    'is_matched' => $isMatched,
                    'confidence_level' => $confidenceLevel,
                    'source' => 'REALTIME',
                    'model_used' => $comparison['model'],
                    'status' => 'PENDING',
                ]);
                $match->save();
            }

            if (! $isReviewed && $match->status === 'PENDING' && $match->is_matched && ($match->wasRecentlyCreated || ! $match->notified_at)) {
                FaceMatchFound::dispatch($match);
                $match->forceFill(['notified_at' => now()])->save();

                // H-01/H-02 — Ghi thông báo FACE_MATCH_SUGGESTED vào đường ống hợp
                // nhất (chuông + email admin theo policy), thay vì tự notify() bên
                // trong constructor của FaceMatchFound. FaceMatchFound giờ chỉ làm
                // realtime broadcast (socket). Nguồn phát sinh gắn SOURCE_AI.
                $missingChild = MissingChild::query()->find($missingPhoto->model_id);

                if ($missingChild) {
                    $notifier->send(
                        NotificationEvent::TYPE_FACE_MATCH_SUGGESTED,
                        $match,
                        $recipients->unique(
                            $recipients->familyOf($missingChild),
                            $recipients->admins(),
                        ),
                        [
                            'case_code' => $missingChild->case_code,
                            'full_name' => $missingChild->full_name,
                            'score_percent' => number_format($score * 100, 1),
                            'confidence_label' => $confidenceLevel,
                            'source' => NotificationEvent::SOURCE_AI,
                        ],
                        null,
                    );
                }
            }
        }

        $photo->forceFill(['ai_status' => 'done'])->save();
    }

    public function failed(\Throwable $exception): void
    {
        Photo::query()
            ->whereKey($this->photoId)
            ->update(['ai_status' => 'failed']);

        Log::error('Face match trigger failed.', [
            'photo_id' => $this->photoId,
            'tracking_id' => $this->trackingId,
            'message' => $exception->getMessage(),
        ]);

        $photo = Photo::query()->find($this->photoId);
        if ($photo) {
            AIJobFailureNotifier::notifyAdmins($photo, static::class, $exception->getMessage());
        }
    }

    private function targetPhotos(Photo $photo)
    {
        $targetType = $photo->model_type === MissingChild::class
            ? FoundChild::class
            : MissingChild::class;

        $query = Photo::query()
            ->where('model_type', $targetType)
            ->where('has_face', 1)
            ->whereNotNull('face_encoding');

        return $query->get();
    }

    private function decryptEncoding(Photo $photo): array
    {
        return json_decode(Crypt::decryptString($photo->face_encoding), true, 512, JSON_THROW_ON_ERROR);
    }
}
