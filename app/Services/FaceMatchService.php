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

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Photo file not found: {$storagePath}");
        }

        $handle = fopen($absolutePath, 'rb');

        try {
            $response = $this->client()
                ->attach('file', $handle, basename($storagePath), [
                    'Content-Type' => mime_content_type($absolutePath) ?: 'application/octet-stream',
                ])
                ->post('/encode');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($response->status() === 422) {
            return [
                'has_face' => false,
                'encoding' => null,
                'model' => config('face_match.model'),
            ];
        }

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('AI encode request failed with status '.$response->status());
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

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Photo file not found: {$storagePath}");
        }

        $handle = fopen($absolutePath, 'rb');

        try {
            $response = $this->client()
                ->attach('file', $handle, basename($storagePath), [
                    'Content-Type' => mime_content_type($absolutePath) ?: 'application/octet-stream',
                ])
                ->post('/detect-face');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
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

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('AI detect-face request failed with status '.$response->status());
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

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('AI compare request failed with status '.$response->status());
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
            if (! is_file($absolutePath)) {
                throw new RuntimeException('Photo file not found: '.basename($absolutePath));
            }
        }

        $idDocumentHandle = fopen($idDocumentAbsolutePath, 'rb');
        $selfieHandle = fopen($selfieAbsolutePath, 'rb');

        try {
            $response = $this->client()
                ->attach('id_document', $idDocumentHandle, basename($idDocumentPath), [
                    'Content-Type' => mime_content_type($idDocumentAbsolutePath) ?: 'application/octet-stream',
                ])
                ->attach('selfie', $selfieHandle, basename($selfiePath), [
                    'Content-Type' => mime_content_type($selfieAbsolutePath) ?: 'application/octet-stream',
                ])
                ->post('/verify');
        } finally {
            if (is_resource($idDocumentHandle)) {
                fclose($idDocumentHandle);
            }

            if (is_resource($selfieHandle)) {
                fclose($selfieHandle);
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

        if (! $response->successful()) {
            throw new AIServiceUnavailableException('AI verify request failed with status '.$response->status());
        }

        return [
            'has_face' => true,
            'score' => (float) $response->json('score'),
            'is_matched' => (bool) $response->json('is_matched'),
            'model' => $response->json('model', config('face_match.model')),
        ];
    }

    private function client(?int $timeout = null): PendingRequest
    {
        $key = config('face_match.internal_key');

        if (! $key) {
            throw new RuntimeException('FACE_MATCH_INTERNAL_KEY is not configured.');
        }

        return Http::baseUrl(rtrim(config('face_match.service_url'), '/'))
            ->timeout($timeout ?? config('face_match.timeout'))
            ->withHeaders(['X-Internal-Key' => $key]);
    }
}
