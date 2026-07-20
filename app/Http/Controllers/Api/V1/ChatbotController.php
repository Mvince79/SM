<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatbotMessageRequest;
use App\Models\ChatbotSession;
use App\Services\Chatbot\ChatbotIntentResolver;
use App\Services\Chatbot\HandoverGuidanceIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function reply(
        ChatbotMessageRequest $request,
        ChatbotIntentResolver $intentResolver,
    ): JsonResponse {
        $validated = $request->validated();
        $sessionToken = $validated['session_token'] ?? Str::random(64);
        $context = [
            'lat' => (float) $validated['lat'],
            'lng' => (float) $validated['lng'],
        ];
        $result = $intentResolver->resolve($validated['message'], $context);

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
}
