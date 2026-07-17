<?php

namespace App\Services\Notification\Contracts;

use App\Models\NotificationEvent;
use App\Models\User;

interface NotificationStrategy
{
    /**
     * @return array{title: string, body: string, action_url: string|null}
     */
    public function render(NotificationEvent $event, User $recipient): array;
}
