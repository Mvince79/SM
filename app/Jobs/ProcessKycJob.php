<?php

namespace App\Jobs;

use App\Models\KycVerification;
use App\Services\EkycService;
use App\Support\AIJobFailureNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessKycJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $kycVerificationId)
    {
        $this->onQueue(config('services.ekyc.queue', 'kyc'));
    }

    public function handle(EkycService $ekycService): void
    {
        $kyc = KycVerification::query()->find($this->kycVerificationId);

        if (!$kyc || $kyc->status !== 'PENDING') {
            return;
        }

        $result = $ekycService->verify($kyc);

        // Loại id_number khỏi bản lưu ocr_raw_response — cột id_number riêng đã mã hóa,
        // tránh tồn tại thêm 1 bản rõ ở cột JSON khác.
        $ocrForStorage = collect($result['ocr_raw_response'])->except(['id_number'])->all();

        $kyc->forceFill([
            'ocr_provider' => $result['ocr_provider'],
            'ocr_raw_response' => $ocrForStorage,
            'face_match_score' => $result['face_match_score'],
        ]);

        if ($result['auto_verifiable']) {
            $kyc->status = 'VERIFIED';
            $kyc->verified_at = Carbon::now();
        }
        // Ngược lại: giữ nguyên PENDING, chờ admin duyệt thủ công (A-08)

        $kyc->save();

        if ($kyc->status === 'VERIFIED' && $kyc->user) {
            $kyc->user->forceFill(['kyc_status' => 'VERIFIED'])->save();
        }

        Log::info('eKYC processed.', [
            'kyc_id' => $kyc->id,
            'provider' => $result['ocr_provider'],
            'face_match_score' => $result['face_match_score'],
            'auto_verified' => $result['auto_verifiable'],
            // KHÔNG log id_number, full_name_on_id, hay path ảnh
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('eKYC processing failed.', [
            'kyc_verification_id' => $this->kycVerificationId,
            'message' => $exception->getMessage(),
        ]);

        AIJobFailureNotifier::notifyAdmins($this->kycVerificationId, static::class, $exception);
    }
}