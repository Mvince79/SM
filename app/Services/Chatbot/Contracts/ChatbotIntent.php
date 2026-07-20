<?php

namespace App\Services\Chatbot\Contracts;

interface ChatbotIntent
{
    public function name(): string;

    public function matches(string $message): bool;

    /**
     * @param  array{lat: float, lng: float}  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array;
}
