<?php

namespace App\Services\Notification;

use App\Jobs\SendFcmNotificationJob;
use App\Mail\RoleBasedNotificationMail;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationDeliveryService
{
    public function __construct(
        private readonly NotificationPolicyResolver $policyResolver,
    ) {}

    /**
     * Render and deliver one event through the database, FCM and email channels.
     *
     * @param  iterable<User>  $users
     */
    public function deliver(NotificationEvent $event, iterable $users): int
    {
        $delivered = 0;
        $seenUserIds = [];

        foreach ($users as $user) {
            if (! $user instanceof User || isset($seenUserIds[$user->getKey()])) {
                continue;
            }

            $seenUserIds[$user->getKey()] = true;
            $content = $this->policyResolver->resolve($event, $user);
            $recipient = NotificationRecipient::query()->firstOrNew([
                'notification_event_id' => $event->getKey(),
                'user_id' => $user->getKey(),
            ]);

            if ($recipient->exists) {
                continue;
            }

            $recipient->fill([
                'role_at_time' => $user->role,
                'rendered_title' => $content['title'],
                'rendered_body' => $content['body'],
                'action_url' => $content['action_url'],
            ])->save();

            SendFcmNotificationJob::dispatch($recipient->getKey());

            if (filled($user->email)) {
                Mail::to($user->email)->queue(new RoleBasedNotificationMail(
                    $content['title'],
                    $content['body'],
                    $content['action_url'],
                ));
            }

            $delivered++;
        }

        return $delivered;
    }
}
