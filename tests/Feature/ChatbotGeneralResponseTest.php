<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotGeneralResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_response_contains_three_rotating_suggestions(): void
    {
        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Thời tiết hôm nay thế nào?',
            'lat' => 10.776,
            'lng' => 106.7,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GENERAL')
            ->assertJsonCount(3, 'data.suggested_questions')
            ->assertJsonFragment(['message' => $response->json('data.message')]);

        $this->assertStringContainsString('Bạn cũng có thể thử hỏi:', $response->json('data.message'));
    }
}
