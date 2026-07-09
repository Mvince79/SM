<?php

namespace App\Services;

use App\Exceptions\AIServiceUnavailableException;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class EkycService
{
    public function __construct(private FaceMatchService $faceMatchService)
    {
    }

    /**
     * Chạy toàn bộ luồng eKYC: OCR CCCD + so khớp khuôn mặt CCCD vs Selfie.
     *
     * @return array{
     *   ocr_provider: string,
     *   ocr_raw_response: array,
     *   face_match_score: float,
     *   name_matches: bool,
     *   auto_verifiable: bool,
     * }
     */
    public function verify(KycVerification $kyc): array
    {
        $provider = config('services.ekyc.provider', 'tesseract');

        $ocrResult = match ($provider) {
            'fpt_ai' => $this->ocrViaFptAi($kyc),
            'vnpt' => $this->ocrViaVnpt($kyc),
            default => $this->ocrViaTesseract($kyc),
        };

        $faceScore = $this->matchFace($kyc);

        $nameMatches = $this->namesMatch(
            $ocrResult['full_name'] ?? null,
            $kyc->user?->full_name
        );

        $highConfidence = (float) config('face_match.high_confidence_threshold', 0.85);

        return [
            'ocr_provider' => $ocrResult['provider'],
            'ocr_raw_response' => $ocrResult,
            'face_match_score' => $faceScore,
            'name_matches' => $nameMatches,
            'auto_verifiable' => $faceScore >= $highConfidence && $nameMatches,
        ];
    }

    // ─── Face match: ảnh mặt trước CCCD vs Selfie ──────────────────────────

    private function matchFace(KycVerification $kyc): float
    {
        if (!$this->faceMatchService->isHealthy()) {
            throw new AIServiceUnavailableException('AI face match service is unavailable.');
        }

        $idPhoto = $this->faceMatchService->encode($kyc->front_image_path, 'private');
        $selfiePhoto = $this->faceMatchService->encode($kyc->selfie_path, 'private');

        if (!$idPhoto['has_face'] || !$selfiePhoto['has_face']) {
            return 0.0;
        }

        $comparison = $this->faceMatchService->compare(
            $idPhoto['encoding'],
            $selfiePhoto['encoding']
        );

        return (float) $comparison['score'];
    }

    // ─── OCR — FPT.AI (nếu có API key) ──────────────────────────────────────

    private function ocrViaFptAi(KycVerification $kyc): array
    {
        $apiKey = config('services.ekyc.fpt_ai.api_key');

        if (!$apiKey) {
            return $this->ocrViaTesseract($kyc, 'fpt_ai_no_key');
        }

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->timeout(config('face_match.timeout', 30))
            ->attach('image', Storage::disk('private')->get($kyc->front_image_path), 'front.jpg')
            ->post(rtrim(config('services.ekyc.fpt_ai.base_url'), '/') . '/vision/idr/vnm');

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('FPT.AI eKYC request failed: ' . $response->status());
        }

        $data = $response->json('data.0', []);

        return [
            'provider' => 'fpt_ai',
            'full_name' => $data['name'] ?? null,
            'dob' => $data['dob'] ?? null,
        ];
    }

    // ─── OCR — VNPT eKYC (nếu có token) ──────────────────────────────────────

    private function ocrViaVnpt(KycVerification $kyc): array
    {
        $tokenId = config('services.ekyc.vnpt.token_id');
        $tokenKey = config('services.ekyc.vnpt.token_key');

        if (!$tokenId || !$tokenKey) {
            return $this->ocrViaTesseract($kyc, 'vnpt_no_token');
        }

        $response = Http::withHeaders([
            'Token-id' => $tokenId,
            'Token-key' => $tokenKey,
        ])
            ->timeout(config('face_match.timeout', 30))
            ->attach('image', Storage::disk('private')->get($kyc->front_image_path), 'front.jpg')
            ->post(rtrim(config('services.ekyc.vnpt.base_url'), '/') . '/ai/idr/v2/idcard/info');

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('VNPT eKYC request failed: ' . $response->status());
        }

        $data = $response->json('object', []);

        return [
            'provider' => 'vnpt',
            'full_name' => $data['name'] ?? null,
            'dob' => $data['birth_day'] ?? null,
        ];
    }

    // ─── OCR fallback: Tesseract chạy local, không phụ thuộc API ngoài ──────

    private function ocrViaTesseract(KycVerification $kyc, string $reason = 'default'): array
    {
        $imagePath = Storage::disk('private')->path($kyc->front_image_path);

        $process = new Process([
            config('services.ekyc.tesseract_path', 'tesseract'),
            $imagePath,
            'stdout',
            '-l',
            'vie+eng',
            '--psm',
            '6',
        ]);
        $process->setTimeout(config('face_match.timeout', 30));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AIServiceUnavailableException('Tesseract OCR failed: ' . $process->getErrorOutput());
        }

        $text = $process->getOutput();

        return [
            'provider' => 'tesseract',
            'fallback_reason' => $reason,
            'full_name' => $this->extractField($text, '/(?:Họ và tên|Full name)[:\s]*([A-ZÀ-Ỹ\s]{4,})/iu'),
            'dob' => $this->extractField($text, '/(?:Ngày sinh|Date of birth)[:\s]*(\d{2}\/\d{2}\/\d{4})/iu'),
            'raw_text_length' => strlen($text), // không lưu raw text đầy đủ — có thể chứa số CCCD
        ];
    }

    private function extractField(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    // ─── So khớp tên OCR vs tên tài khoản (bỏ dấu, không phân biệt hoa/thường) ──

    private function namesMatch(?string $ocrName, ?string $registeredName): bool
    {
        if (!$ocrName || !$registeredName) {
            return false;
        }

        $normalize = static fn(string $v): string => preg_replace('/\s+/', ' ', trim(Str::lower(Str::ascii($v))));

        return $normalize($ocrName) === $normalize($registeredName);
    }
}