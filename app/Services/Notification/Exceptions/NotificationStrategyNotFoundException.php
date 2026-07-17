<?php

namespace App\Services\Notification\Exceptions;

use RuntimeException;

class NotificationStrategyNotFoundException extends RuntimeException
{
    public static function for(string $eventType, string $role): self
    {
        return new self("Không tìm thấy notification strategy cho sự kiện [{$eventType}] và role [{$role}].");
    }
}
