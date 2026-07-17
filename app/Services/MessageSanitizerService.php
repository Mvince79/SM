<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MissingChild;

class MessageSanitizerService
{
    public const MASKED_PHONE_MESSAGE = '[Số điện thoại đã được ẩn — vui lòng liên hệ qua điểm bàn giao]';

    /**
     * Vietnamese mobile numbers beginning with 03/05/07/08/09.
     * Common visual separators are accepted, but the number must contain 10 digits.
     */
    private const VIETNAMESE_MOBILE_PATTERN =
        '/(?<!\d)0(?:3|5|7|8|9)(?:[ .-]?\d){8}(?!\d)/u';

    public function sanitizeForMessage(Message $message): ?string
    {
        return $this->sanitize($message->body, $message->missingChild);
    }

    public function sanitize(?string $body, ?MissingChild $case): ?string
    {
        if ($body === null || $case === null || $this->isClosed($case)) {
            return $body;
        }

        return preg_replace(self::VIETNAMESE_MOBILE_PATTERN, self::MASKED_PHONE_MESSAGE, $body);
    }

    private function isClosed(MissingChild $case): bool
    {
        return in_array(strtoupper((string) $case->status), ['FOUND', 'CLOSED'], true);
    }
}
