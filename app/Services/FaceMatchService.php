<?php

namespace App\Services;

use App\Exceptions\AIServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FaceMatchService
{
    public function isHealthy(): bool
    {
        try {
            return $this->client(config('face_match.health_timeout'))
                ->get('/health')
                ->ok();
        } catch (ConnectionException) {
            return false;
        }
    }

    public function encode(string $storagePath, string $disk = 'public'): array
    {
        $absolutePath = Storage::disk($disk)->path($storagePath);

        if (!is_file($absolutePath)) {
            throw new RuntimeException("Photo file not found: {$storagePath}");
        }

        [$uploadPath, $uploadName, $mime, $isTemp] = $this->prepareUploadFile($absolutePath);

        $handle = fopen($uploadPath, 'rb');

        try {
            $response = $this->client()
                ->attach('file', $handle, $uploadName, [
                    'Content-Type' => $mime,
                ])
                ->post('/encode');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($isTemp) {
                @unlink($uploadPath);
            }
        }

        if ($response->status() === 422) {
            return [
                'has_face' => false,
                'encoding' => null,
                'model' => config('face_match.model'),
            ];
        }

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('AI encode request failed with status ' . $response->status());
        }

        return [
            'has_face' => (bool) $response->json('has_face', true),
            'encoding' => $response->json('encoding'),
            'model' => $response->json('model', config('face_match.model')),
        ];
    }

    public function detectFace(string $storagePath): array
    {
        $absolutePath = Storage::disk('public')->path($storagePath);

        if (!is_file($absolutePath)) {
            throw new RuntimeException("Photo file not found: {$storagePath}");
        }

        [$uploadPath, $uploadName, $mime, $isTemp] = $this->prepareUploadFile($absolutePath);

        $handle = fopen($uploadPath, 'rb');

        try {
            $response = $this->client()
                ->attach('file', $handle, $uploadName, [
                    'Content-Type' => $mime,
                ])
                ->post('/detect-face');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($isTemp) {
                @unlink($uploadPath);
            }
        }

        if ($response->status() === 422) {
            return [
                'has_face' => false,
                'bounding_box' => null,
                'crop_image_base64' => null,
                'detected_gender' => 'UNKNOWN',
            ];
        }

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('AI detect-face request failed with status ' . $response->status());
        }

        return [
            'has_face' => (bool) $response->json('has_face', true),
            'bounding_box' => $response->json('bounding_box'),
            'crop_image_base64' => $response->json('crop_image_base64'),
            'detected_gender' => $response->json('detected_gender', 'UNKNOWN'),
        ];
    }

    public function compare(array $encodingA, array $encodingB): array
    {
        $response = $this->client()->post('/compare', [
            'encoding_a' => $encodingA,
            'encoding_b' => $encodingB,
        ]);

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('AI compare request failed with status ' . $response->status());
        }

        return [
            'score' => (float) $response->json('score'),
            'is_matched' => (bool) $response->json('is_matched'),
            'model' => $response->json('model', config('face_match.model')),
        ];
    }

    /**
     * Compare an identity-document portrait with a selfie in one AI request.
     *
     * @return array{has_face: bool, score: float, is_matched: bool, model: string}
     */
    public function verifyImages(
        string $idDocumentPath,
        string $selfiePath,
        string $disk = 'private',
    ): array {
        $idDocumentAbsolutePath = Storage::disk($disk)->path($idDocumentPath);
        $selfieAbsolutePath = Storage::disk($disk)->path($selfiePath);

        foreach ([$idDocumentAbsolutePath, $selfieAbsolutePath] as $absolutePath) {
            if (!is_file($absolutePath)) {
                throw new RuntimeException('Photo file not found: ' . basename($absolutePath));
            }
        }

        [$idUploadPath, $idUploadName, $idMime, $idIsTemp] = $this->prepareUploadFile($idDocumentAbsolutePath);
        [$selfieUploadPath, $selfieUploadName, $selfieMime, $selfieIsTemp] = $this->prepareUploadFile($selfieAbsolutePath);

        $idDocumentHandle = fopen($idUploadPath, 'rb');
        $selfieHandle = fopen($selfieUploadPath, 'rb');

        try {
            $response = $this->client()
                ->attach('id_document', $idDocumentHandle, $idUploadName, [
                    'Content-Type' => $idMime,
                ])
                ->attach('selfie', $selfieHandle, $selfieUploadName, [
                    'Content-Type' => $selfieMime,
                ])
                ->post('/verify');
        } finally {
            if (is_resource($idDocumentHandle)) {
                fclose($idDocumentHandle);
            }

            if (is_resource($selfieHandle)) {
                fclose($selfieHandle);
            }

            if ($idIsTemp) {
                @unlink($idUploadPath);
            }

            if ($selfieIsTemp) {
                @unlink($selfieUploadPath);
            }
        }

        if ($response->status() === 422) {
            return [
                'has_face' => false,
                'score' => 0.0,
                'is_matched' => false,
                'model' => config('face_match.model'),
            ];
        }

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('AI verify request failed with status ' . $response->status());
        }

        return [
            'has_face' => true,
            'score' => (float) $response->json('score'),
            'is_matched' => (bool) $response->json('is_matched'),
            'model' => $response->json('model', config('face_match.model')),
        ];
    }

    /**
     * AI service chỉ chấp nhận jpeg/png. Nếu ảnh gốc là định dạng khác
     * (vd: webp), convert sang jpeg tạm thời trước khi upload.
     *
     * @return array{0: string, 1: string, 2: string, 3: bool} [uploadPath, uploadName, mime, isTemp]
     */
    private function prepareUploadFile(string $absolutePath): array
    {
        $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';

        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return [$absolutePath, basename($absolutePath), $mime, false];
        }

        $image = match ($mime) {
            'image/webp' => @imagecreatefromwebp($absolutePath),
            'image/gif' => @imagecreatefromgif($absolutePath),
            'image/bmp' => @imagecreatefrombmp($absolutePath),
            default => @imagecreatefromstring(file_get_contents($absolutePath)),
        };

        if ($image === false || $image === null) {
            throw new RuntimeException("Unable to decode image for conversion: {$absolutePath} (mime: {$mime})");
        }

        // Nền trắng cho ảnh có alpha channel (webp/png trong suốt) khi convert sang jpeg
        $width = imagesx($image);
        $height = imagesy($image);
        $flattened = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($flattened, 255, 255, 255);
        imagefill($flattened, 0, 0, $white);
        imagecopy($flattened, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        $tmpPath = tempnam(sys_get_temp_dir(), 'facematch_') . '.jpg';
        imagejpeg($flattened, $tmpPath, 90);
        imagedestroy($flattened);

        $uploadName = pathinfo($absolutePath, PATHINFO_FILENAME) . '.jpg';

        return [$tmpPath, $uploadName, 'image/jpeg', true];
    }

    private function client(?int $timeout = null): PendingRequest
    {
        $key = config('face_match.internal_key');

        if (!$key) {
            throw new RuntimeException('FACE_MATCH_INTERNAL_KEY is not configured.');
        }

        return Http::baseUrl(rtrim(config('face_match.service_url'), '/'))
            ->timeout($timeout ?? config('face_match.timeout'))
            ->withHeaders(['X-Internal-Key' => $key]);
    }
}