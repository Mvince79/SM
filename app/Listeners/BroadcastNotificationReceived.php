<?php

namespace App\Listeners;

use App\Events\NotificationCountUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastNotificationReceived
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database')
            return;

        broadcast(new NotificationCountUpdated(
            $event->notifiable->id,
            $event->notifiable->unreadNotifications()->count()
        ))->toOthers();
    }
}
