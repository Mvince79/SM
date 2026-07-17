<?php

namespace Tests\Feature;

use App\Models\FcmToken;
use App\Models\User;
use App\Services\Notification\FcmHttpV1PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmHttpV1PushSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_http_v1_payload_to_each_active_device_token(): void
    {
        config()->set('services.firebase.project_id', 'timtre-test');
        config()->set('services.firebase.access_token', 'oauth-token');
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'message-id']),
        ]);
        $user = User::factory()->create();
        FcmToken::query()->create([
            'user_id' => $user->id,
            'token' => 'active-device-token',
            'device_type' => 'WEB',
            'is_active' => 1,
        ]);
        FcmToken::query()->create([
            'user_id' => $user->id,
            'token' => 'inactive-device-token',
            'device_type' => 'WEB',
            'is_active' => 0,
        ]);

        app(FcmHttpV1PushSender::class)->send($user, [
            'title' => 'Đã xác nhận kết quả',
            'body' => 'Nội dung theo role',
            'action_url' => 'https://example.test/case/1',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://fcm.googleapis.com/v1/projects/timtre-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer oauth-token')
            && $request['message']['token'] === 'active-device-token'
            && $request['message']['notification']['title'] === 'Đã xác nhận kết quả'
            && $request['message']['data']['action_url'] === 'https://example.test/case/1');
    }
}
