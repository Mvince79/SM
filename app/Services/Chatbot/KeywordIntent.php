<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Contracts\ChatbotIntent;
use Illuminate\Support\Str;

abstract class KeywordIntent implements ChatbotIntent
{
    /** @return list<string> */
    abstract protected function keywords(): array;

    abstract protected function responseMessage(): string;

    public function matches(string $message): bool
    {
        $normalized = Str::lower(Str::ascii($message));
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        return Str::contains($normalized, $this->keywords());
    }

    public function handle(array $context): array
    {
        return [
            'message' => $this->responseMessage(),
            'handover_points' => [],
        ];
    }
}
