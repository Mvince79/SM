<?php

namespace App\Services;

use App\Exceptions\AIServiceUnavailableException;
use App\Models\KycVerification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class EkycService
{
    public function __construct(private FaceMatchService $faceMatchService) {}

    /**
     * Run the full eKYC flow: CMND/CCCD OCR and document-photo/selfie face matching.
     *
     * @return array{
     *   ocr_provider: string,
     *   ocr_raw_response: array,
     *   id_type: string|null,
     *   face_match_score: float,
     *   name_matches: bool,
     *   id_number_matches: bool,
     *   auto_verifiable: bool,
     * }
     */
    public function verify(KycVerification $kyc): array
    {
        $ocrResult = $this->recognizeIdentityDocument($kyc);
        $faceScore = $this->matchSelfieWithId($kyc);
        $nameMatches = $this->namesMatch(
            $ocrResult['full_name'] ?? null,
            $kyc->user?->full_name
        );
        $idNumberMatches = $this->idNumbersMatch(
            $ocrResult['id_number'] ?? null,
            $kyc->decrypted_id_number,
        );
        $highConfidence = (float) config('face_match.high_confidence_threshold', 0.85);

        return [
            'ocr_provider' => $ocrResult['provider'],
            'ocr_raw_response' => $ocrResult,
            'id_type' => $ocrResult['id_type'],
            'face_match_score' => $faceScore,
            'name_matches' => $nameMatches,
            'id_number_matches' => $idNumberMatches,
            'auto_verifiable' => $faceScore >= $highConfidence && $nameMatches && $idNumberMatches,
        ];
    }

    /**
     * Normalize every OCR provider to one safe internal contract.
     *
     * @return array{
     *   provider: string,
     *   id_number: string|null,
     *   id_type: string|null,
     *   full_name: string|null,
     *   dob: string|null,
     *   document_type: string|null,
     *   fallback_reason?: string,
     *   raw_text_length?: int,
     * }
     */
    public function recognizeIdentityDocument(KycVerification $kyc): array
    {
        return match (config('services.ekyc.provider', 'tesseract')) {
            'fpt_ai' => $this->ocrViaFptAi($kyc),
            'vnpt' => $this->ocrViaVnpt($kyc),
            default => $this->ocrViaTesseract($kyc),
        };
    }

    public function matchSelfieWithId(KycVerification $kyc): float
    {
        if (! $this->faceMatchService->isHealthy()) {
            throw new AIServiceUnavailableException('AI face match service is unavailable.');
        }

        $verification = $this->faceMatchService->verifyImages(
            $kyc->front_image_path,
            $kyc->selfie_path,
            'private',
        );

        return $verification['has_face'] ? (float) $verification['score'] : 0.0;
    }

    private function ocrViaFptAi(KycVerification $kyc): array
    {
        $apiKey = config('services.ekyc.fpt_ai.api_key');

        if (! $apiKey) {
            return $this->ocrViaTesseract($kyc, 'fpt_ai_no_key');
        }

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->timeout(config('face_match.timeout', 30))
            ->attach('image', Storage::disk('private')->get($kyc->front_image_path), 'front.jpg')
            ->post(rtrim(config('services.ekyc.fpt_ai.base_url'), '/').'/vision/idr/vnm');

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('FPT.AI eKYC request failed: '.$response->status());
        }

        if ((int) $response->json('errorCode', -1) !== 0) {
            throw new AIServiceUnavailableException(
                'FPT.AI eKYC rejected the image: '.(string) $response->json('errorMessage', 'unknown error')
            );
        }

        $data = $response->json('data.0', []);

        return $this->normalizeIdentityResult('fpt_ai', [
            'id_number' => $data['id'] ?? null,
            'full_name' => $data['name'] ?? null,
            'dob' => $data['dob'] ?? null,
            'document_type' => $data['type_new'] ?? $data['type'] ?? null,
        ]);
    }

    private function ocrViaVnpt(KycVerification $kyc): array
    {
        $tokenId = config('services.ekyc.vnpt.token_id');
        $tokenKey = config('services.ekyc.vnpt.token_key');

        if (! $tokenId || ! $tokenKey) {
            return $this->ocrViaTesseract($kyc, 'vnpt_no_token');
        }

        $response = Http::withHeaders([
            'Token-id' => $tokenId,
            'Token-key' => $tokenKey,
        ])
            ->timeout(config('face_match.timeout', 30))
            ->attach('image', Storage::disk('private')->get($kyc->front_image_path), 'front.jpg')
            ->post(rtrim(config('services.ekyc.vnpt.base_url'), '/').'/ai/idr/v2/idcard/info');

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('VNPT eKYC request failed: '.$response->status());
        }

        $data = $response->json('object', []);

        return $this->normalizeIdentityResult('vnpt', [
            'id_number' => $data['id']
                ?? $data['id_number']
                ?? $data['identity_number']
                ?? null,
            'full_name' => $data['name'] ?? $data['full_name'] ?? null,
            'dob' => $data['birth_day'] ?? $data['dob'] ?? null,
            'document_type' => $data['type']
                ?? $data['card_type']
                ?? $data['object_type']
                ?? null,
        ]);
    }

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

        if (! $process->isSuccessful()) {
            throw new AIServiceUnavailableException('Tesseract OCR failed: '.$process->getErrorOutput());
        }

        $text = $process->getOutput();
        $result = $this->normalizeIdentityResult('tesseract', [
            'id_number' => $this->extractIdentityNumber($text),
            'full_name' => $this->extractField(
                $text,
                '/(?:Họ và tên|Ho va ten|Full name)[:\s]*([^\r\n]{4,})/iu'
            ),
            'dob' => $this->extractField(
                $text,
                '/(?:Ngày sinh|Ngay sinh|Date of birth)[:\s]*(\d{2}[\/.-]\d{2}[\/.-]\d{4})/iu'
            ),
            'document_type' => null,
        ]);

        return $result + [
            'fallback_reason' => $reason,
            'raw_text_length' => strlen($text),
        ];
    }

    /**
     * @param  array{id_number?: mixed, full_name?: mixed, dob?: mixed, document_type?: mixed}  $data
     * @return array{
     *   provider: string,
     *   id_number: string|null,
     *   id_type: string|null,
     *   full_name: string|null,
     *   dob: string|null,
     *   document_type: string|null,
     * }
     */
    private function normalizeIdentityResult(string $provider, array $data): array
    {
        $idNumber = $this->normalizeIdentityNumber($data['id_number'] ?? null);
        $documentType = $this->normalizeTextValue($data['document_type'] ?? null);

        return [
            'provider' => $provider,
            'id_number' => $idNumber,
            'id_type' => $this->detectIdentityType($idNumber, $documentType),
            'full_name' => $this->normalizeTextValue($data['full_name'] ?? null),
            'dob' => $this->normalizeDate($data['dob'] ?? null),
            'document_type' => $documentType,
        ];
    }

    private function detectIdentityType(?string $idNumber, ?string $documentType): ?string
    {
        $type = Str::lower(Str::ascii((string) $documentType));

        if (str_contains($type, 'cmnd_09') || str_contains($type, 'cmnd_9') || $type === 'old') {
            return 'CMND_9';
        }

        if (str_contains($type, 'cccd')
            || str_contains($type, 'cmnd_12')
            || str_contains($type, '12')
            || $type === 'new') {
            return 'CCCD_12';
        }

        return match (strlen((string) $idNumber)) {
            9 => 'CMND_9',
            12 => 'CCCD_12',
            default => null,
        };
    }

    private function extractIdentityNumber(string $text): ?string
    {
        $patterns = [
            '/(?:Số|So|No\.?|ID(?:\s+No)?|Number)[:\s]*([0-9][0-9 .-]{7,15}[0-9])/iu',
            '/(?<!\d)(\d{12}|\d{9})(?!\d)/',
        ];

        foreach ($patterns as $pattern) {
            $value = $this->extractField($text, $pattern);
            $normalized = $this->normalizeIdentityNumber($value);

            if (in_array(strlen((string) $normalized), [9, 12], true)) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeIdentityNumber(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return in_array(strlen($digits), [9, 12], true) ? $digits : null;
    }

    private function normalizeTextValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $normalized === '' || strtoupper($normalized) === 'N/A' ? null : $normalized;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = $this->normalizeTextValue($value);

        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);

                if ($date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try the next known provider format.
            }
        }

        return null;
    }

    private function extractField(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function namesMatch(?string $ocrName, ?string $registeredName): bool
    {
        if (! $ocrName || ! $registeredName) {
            return false;
        }

        $normalize = static fn (string $value): string => preg_replace(
            '/\s+/',
            ' ',
            trim(Str::lower(Str::ascii($value)))
        );

        return $normalize($ocrName) === $normalize($registeredName);
    }

    private function idNumbersMatch(?string $ocrIdNumber, ?string $submittedIdNumber): bool
    {
        $ocrIdNumber = $this->normalizeIdentityNumber($ocrIdNumber);
        $submittedIdNumber = $this->normalizeIdentityNumber($submittedIdNumber);

        return $ocrIdNumber !== null
            && $submittedIdNumber !== null
            && hash_equals($submittedIdNumber, $ocrIdNumber);
    }
}
