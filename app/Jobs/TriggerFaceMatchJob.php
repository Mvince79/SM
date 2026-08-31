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
use App\Support\AiCallRetry;
use Illuminate\Database\UniqueConstraintViolationException;
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
        // Chạy BẤT ĐỒNG BỘ (queue 'face-match') — khớp với supervisor-face-match
        // trong config/horizon.php. So khớp chạy nền, không block request upload.
        // Cần worker (php artisan queue:work / horizon) đang chạy.
        $this->onQueue('face-match');
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

        // Ẩn: hồ sơ chứa ảnh này đã giải quyết (đã đoàn tụ/đóng/...) → không quét nữa,
        // tránh AI tiếp tục so khớp ảnh mới của hồ sơ đã "xong việc".
        if ($this->isRecordResolved($photo)) {
            return;
        }

        $photo->forceFill(['ai_status' => 'processing'])->save();

        try {
            // isHealthy là tiền kiểm nhanh; retry vài lần để vượt AI giật tạm thời
            // (Laravel Queue retry vô tác dụng vì job ép sync — xem AiCallRetry).
            AiCallRetry::run(function () use ($faceMatchService) {
                if (! $faceMatchService->isHealthy()) {
                    throw new AIServiceUnavailableException('AI service is unavailable.');
                }
            });
        } catch (AIServiceUnavailableException $e) {
            // AI service không phản hồi (kể cả sau retry): đánh dấu ảnh 'pending' để
            // thử lại sau, báo admin, KHÔNG throw (đặc biệt khi chạy đồng bộ trong request upload).
            $photo->forceFill(['ai_status' => 'pending'])->save();
            Log::warning('Face match skipped (AI unavailable), left pending.', [
                'photo_id' => $photo->id,
            ]);
            AIJobFailureNotifier::notifyAdmins($photo, static::class, 'AI service unavailable during face match.');

            return;
        }

        $sourceEncoding = $this->decryptEncoding($photo);

        foreach ($this->targetPhotos($photo) as $targetPhoto) {
            $targetEncoding = $this->decryptEncoding($targetPhoto);

            try {
                // Retry nội bộ (vài lần, backoff ms) để vượt AI giật tạm thời giữa
                // vòng lặp; nếu cạn lần thử, ném lại để catch bên dưới xử lý graceful.
                $comparison = AiCallRetry::run(fn () => $faceMatchService->compare($sourceEncoding, $targetEncoding));
            } catch (AIServiceUnavailableException $e) {
                // AI service chập chờn giữa vòng lặp so khớp: đánh dấu ảnh 'pending'
                // để thử lại sau, báo admin, KHÔNG throw ra ngoài request upload.
                $photo->forceFill(['ai_status' => 'pending'])->save();
                Log::warning('Face match compare failed (AI unavailable), left pending.', [
                    'photo_id' => $photo->id,
                    'message' => $e->getMessage(),
                ]);
                AIJobFailureNotifier::notifyAdmins($photo, static::class, $e->getMessage());

                return;
            } catch (\Throwable $e) {
                $photo->forceFill(['ai_status' => 'failed'])->save();
                Log::error('Face match compare failed.', [
                    'photo_id' => $photo->id,
                    'message' => $e->getMessage(),
                ]);
                AIJobFailureNotifier::notifyAdmins($photo, static::class, $e->getMessage());

                return;
            }

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

                try {
                    $match->save();
                } catch (UniqueConstraintViolationException $e) {
                    // Race condition: một tiến trình khác (vd. 2 upload chạy đồng thời)
                    // đã chèn bản ghi cùng cặp (missing_photo_id, found_photo_id) trước ta.
                    // Vì bảng có unique constraint, INSERT của ta bị từ chối. Thay vì ném
                    // lỗi (có thể crash request upload do job chạy sync), lấy bản ghi đã
                    // tồn tại để tiếp tục xử lý (notify đúng 1 lần, không trùng lặp).
                    $match = FaceMatchResult::query()->where([
                        'missing_photo_id' => $missingPhoto->id,
                        'found_photo_id' => $foundPhoto->id,
                    ])->firstOrFail();
                }
            }

            if (! $isReviewed && $match->status === 'PENDING' && $match->is_matched && ($match->wasRecentlyCreated || ! $match->notified_at)) {
                try {
                    FaceMatchFound::dispatch($match);
                } catch (\Throwable $e) {
                    // Realtime broadcast (WebSocket) thất bại KHÔNG được làm hỏng
                    // luồng chính — thông báo DB (TYPE_FACE_MATCH_SUGGESTED) đã ghi ở dưới.
                    Log::warning('FaceMatchFound broadcast failed (realtime only).', [
                        'match_id' => $match->id,
                        'message' => $e->getMessage(),
                    ]);
                }
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

        // Dùng whereNotExists(hồ sơ ĐÃ giải quyết) thay vì whereExists(hồ sơ chưa giải
        // quyết): ảnh "mồ côi" (không có hồ sơ cha) vẫn được giữ lại như hành vi cũ,
        // chỉ ảnh của hồ sơ ĐÃ giải quyết mới bị loại khỏi pool quét. Base Query\Builder
        // (từ whereNotExists) không có Eloquent scope nên viết điều kiện bằng SQL.
        $query = Photo::query()
            ->where('model_type', $targetType)
            ->where('has_face', 1)
            ->whereNotNull('face_encoding')
            ->whereNotExists(function ($q) use ($targetType) {
                if ($targetType === MissingChild::class) {
                    // Hồ sơ trẻ mất tích đã kết thúc (đã đoàn tụ/đóng/từ chối/...).
                    $q->selectRaw('1')
                        ->from('missing_children')
                        ->whereColumn('missing_children.id', 'photos.model_id')
                        ->whereIn('missing_children.status', MissingChild::CLOSED_STATUSES);
                } else {
                    // Hồ sơ trẻ tìm thấy đã kết thúc, HOẶC đã có kết quả đối chiếu
                    // CONFIRMED trỏ đến một hồ sơ mất tích đã kết thúc (đã khớp + phụ
                    // huynh xác nhận về nhà → ẩn khỏi pool quét để AI không so khớp lặp).
                    $q->selectRaw('1')
                        ->from('found_children')
                        ->whereColumn('found_children.id', 'photos.model_id')
                        ->where(function ($q2) {
                            $q2->whereIn('found_children.status', [FoundChild::STATUS_CLOSED, FoundChild::STATUS_REJECTED])
                                ->orWhereExists(function ($q3) {
                                    $q3->selectRaw('1')
                                        ->from('face_match_results')
                                        ->join('missing_children', 'missing_children.id', 'face_match_results.missing_id')
                                        ->whereColumn('face_match_results.found_id', 'found_children.id')
                                        ->where('face_match_results.status', 'CONFIRMED')
                                        ->whereIn('missing_children.status', MissingChild::CLOSED_STATUSES);
                                });
                        });
                }
            });

        return $query->get();
    }

    /**
     * Hồ sơ chứa ảnh đã giải quyết (không còn nằm trong pool quét đối chiếu)?
     * Dùng chung scopeNotResolved() của model tương ứng để có 1 nguồn sự thật.
     */
    private function isRecordResolved(Photo $photo): bool
    {
        $modelClass = $photo->model_type; // MissingChild|FoundChild
        $record = $modelClass::query()->find($photo->model_id);

        if (! $record) {
            return false;
        }

        // "đã giải quyết" = không thỏa scopeNotResolved().
        return ! $modelClass::query()
            ->whereKey($record->getKey())
            ->notResolved()
            ->exists();
    }

    private function decryptEncoding(Photo $photo): array
    {
        return json_decode(Crypt::decryptString($photo->face_encoding), true, 512, JSON_THROW_ON_ERROR);
    }
}
