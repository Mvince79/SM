<?php

namespace Tests\Feature;

use App\Models\ChatbotSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatbotKnowledgeIntentTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('questions')]
    public function test_api_resolves_knowledge_intents(string $question, string $expectedIntent): void
    {
        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => $question,
            'lat' => 10.776,
            'lng' => 106.7,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', $expectedIntent)
            ->assertJsonPath('data.handover_points', []);

        $this->assertSame($expectedIntent, ChatbotSession::query()->sole()->intent);
    }

    public static function questions(): array
    {
        return [
            'kyc' => ['KYC cần giấy tờ gì?', 'KYC_REQUIREMENT'],
            'face_match' => ['AI có đúng không khi đối chiếu khuôn mặt?', 'FACE_MATCH_ACCURACY'],
            'privacy' => ['Số điện thoại có lộ không?', 'DATA_PRIVACY'],
            'report_missing' => ['Tôi muốn báo tin mất tích', 'REPORT_MISSING_CHILD'],
            'support' => ['Tôi cần liên hệ admin hỗ trợ', 'SUPPORT_TICKET'],
        ];
    }
}
