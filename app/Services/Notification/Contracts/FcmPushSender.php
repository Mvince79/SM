<?php

namespace App\Services\Notification\Contracts;

use App\Models\User;

interface FcmPushSender
{
    /**
     * @param  array{title: string, body: string, action_url: string|null}  $content
     */
    public function send(User $user, array $content): void;
}
