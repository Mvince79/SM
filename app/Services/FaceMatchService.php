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

        if (!$response->successful()) {
            throw new AIServiceUnavailableException('AI encode request failed with status ' . $response->status());
        }

        return [
            'has_face' => (bool) $response->json('has_face', true),
            'encoding' => $response->json('encoding'),
            'model' => $response->json('model', config('face_match.model')),
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
