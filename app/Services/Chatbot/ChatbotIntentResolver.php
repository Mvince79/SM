<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Contracts\ChatbotIntent;

class ChatbotIntentResolver
{
    /** @var list<ChatbotIntent> */
    private array $intents;

    public function __construct(HandoverGuidanceIntent $handoverGuidanceIntent)
    {
        $this->intents = [$handoverGuidanceIntent];
    }

    /**
     * @param  array{lat: float, lng: float}  $context
     * @return array<string, mixed>
     */
    public function resolve(string $message, array $context): array
    {
        foreach ($this->intents as $intent) {
            if ($intent->matches($message)) {
                return [
                    'intent' => $intent->name(),
                    ...$intent->handle($context),
                ];
            }
        }

        return [
            'intent' => 'GENERAL',
            'message' => 'Tôi chưa hiểu yêu cầu. Bạn có thể hỏi “Tôi nên đưa bé đến đâu?” để nhận gợi ý điểm bàn giao an toàn.',
            'handover_points' => [],
        ];
    }
}
