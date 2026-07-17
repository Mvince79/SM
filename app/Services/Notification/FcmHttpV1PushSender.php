<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Services\Notification\Contracts\FcmPushSender;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class FcmHttpV1PushSender implements FcmPushSender
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(User $user, array $content): void
    {
        $tokens = $user->fcmTokens()->where('is_active', 1)->get();

        if ($tokens->isEmpty()) {
            return;
        }

        [$projectId, $accessToken] = $this->credentials();
        $client = $this->client($accessToken);

        foreach ($tokens as $token) {
            $response = $client->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token' => $token->token,
                        'notification' => [
                            'title' => $content['title'],
                            'body' => $content['body'],
                        ],
                        'data' => [
                            'action_url' => (string) ($content['action_url'] ?? ''),
                        ],
                    ],
                ]
            );

            if ($this->isUnregisteredToken($response)) {
                $token->update(['is_active' => 0]);

                continue;
            }

            $response->throw();
            $token->forceFill(['last_used_at' => now()])->save();
        }
    }

    private function client(string $accessToken): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->retry(2, 250, throw: false);
    }

    private function isUnregisteredToken(Response $response): bool
    {
        return collect($response->json('error.details', []))
            ->contains(fn (mixed $detail): bool => is_array($detail) && ($detail['errorCode'] ?? null) === 'UNREGISTERED');
    }

    /**
     * @return array{string, string}
     */
    private function credentials(): array
    {
        $configuredProjectId = config('services.firebase.project_id');
        $configuredAccessToken = config('services.firebase.access_token');

        if (filled($configuredProjectId) && filled($configuredAccessToken)) {
            return [(string) $configuredProjectId, (string) $configuredAccessToken];
        }

        $credentials = $this->serviceAccountCredentials();
        $projectId = $configuredProjectId ?: ($credentials['project_id'] ?? null);

        if (! is_string($projectId) || $projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID chưa được cấu hình.');
        }

        $cacheKey = 'firebase:fcm-access-token:'.sha1((string) $credentials['client_email']);
        $accessToken = Cache::remember(
            $cacheKey,
            now()->addMinutes(50),
            fn (): string => $this->mintAccessToken($credentials)
        );

        return [$projectId, $accessToken];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceAccountCredentials(): array
    {
        $path = config('services.firebase.credentials');

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            throw new RuntimeException('GOOGLE_APPLICATION_CREDENTIALS phải trỏ tới service-account JSON có thể đọc.');
        }

        try {
            $credentials = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Service-account JSON không hợp lệ.', previous: $exception);
        }

        foreach (['client_email', 'private_key', 'token_uri'] as $required) {
            if (blank($credentials[$required] ?? null)) {
                throw new RuntimeException("Service-account JSON thiếu trường [{$required}].");
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function mintAccessToken(array $credentials): string
    {
        $now = now()->timestamp;
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::MESSAGING_SCOPE,
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedJwt = "{$header}.{$claims}";

        if (! openssl_sign($unsignedJwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Không thể ký JWT cho Firebase service account.');
        }

        $assertion = $unsignedJwt.'.'.$this->base64UrlEncode($signature);
        $response = Http::asForm()->post((string) $credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ])->throw();

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Firebase OAuth response không có access_token.');
        }

        return $accessToken;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
