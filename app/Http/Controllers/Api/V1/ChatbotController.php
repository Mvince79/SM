<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatbotMessageRequest;
use App\Models\ChatbotSession;
use App\Services\Chatbot\ChatbotIntentResolver;
use App\Services\Chatbot\HandoverGuidanceIntent;
use App\Services\Gemini\ChatbotGeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function reply(
        ChatbotMessageRequest $request,
        ChatbotIntentResolver $intentResolver,
        ChatbotGeminiService $gemini,
    ): JsonResponse {
        $validated = $request->validated();
        $sessionToken = $validated['session_token'] ?? Str::random(64);
        $context = [
            'lat' => (float) $validated['lat'],
            'lng' => (float) $validated['lng'],
        ];

        // Bước 5: Gemini là lớp chính; intent resolver chỉ là fallback duy nhất
        // khi Gemini lỗi mạng / timeout / hết quota (service trả null).
        $result = $gemini->reply([
            'message' => $validated['message'],
            'lat' => $context['lat'],
            'lng' => $context['lng'],
            'history' => $this->loadHistory($sessionToken),
        ]) ?? $intentResolver->resolve($validated['message'], $context);

        $session = ChatbotSession::query()->firstOrNew([
            'session_token' => $sessionToken,
        ]);
        $collectedData = $session->collected_data ?? [];
        $messages = $collectedData['messages'] ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];
        $messages[] = [
            'role' => 'assistant',
            'content' => $result['message'],
        ];

        $session->fill([
            'user_id' => $request->user('sanctum')?->getAuthIdentifier(),
            'intent' => $result['intent'],
            'current_step' => $result['intent'],
            'collected_data' => [
                'messages' => array_slice($messages, -20),
                'location' => $context,
            ],
            'status' => $result['intent'] === HandoverGuidanceIntent::NAME
                ? 'COMPLETED'
                : 'ACTIVE',
            'completed_action' => $result['intent'] === HandoverGuidanceIntent::NAME
                ? 'showed_handover_points'
                : null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 300, ''),
        ])->save();

        return response()->json([
            'data' => [
                'session_token' => $sessionToken,
                ...$result,
            ],
        ]);
    }

    /**
     * Lấy tối đa 6 tin nhắn gần nhất của phiên để Gemini có ngữ cảnh đa lượt.
     *
     * @return list<array{role: string, content: string}>
     */
    private function loadHistory(string $sessionToken): array
    {
        $session = ChatbotSession::query()->where('session_token', $sessionToken)->first();
        $messages = $session?->collected_data['messages'] ?? [];

        return array_slice((array) $messages, -6);
    }
}
