<?php

namespace App\Services\Notification;

use App\Events\NotificationCountUpdated;
use App\Jobs\SendFcmNotificationJob;
use App\Mail\DynamicNotificationMail;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationDeliveryService
{
    public function __construct(
        private readonly NotificationPolicyResolver $policyResolver,
    ) {}

    /**
     * Render và gửi một event qua các kênh (DB/email/push) tới từng người nhận.
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

            // Một người nhận render lỗi không được làm chết cả batch những người còn lại.
            try {
                $content = $this->policyResolver->resolve($event, $user);
            } catch (\Throwable $e) {
                Log::error('Render nội dung thông báo thất bại', [
                    'event_type' => $event->event_type,
                    'user_id' => $user->getKey(),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

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

            $this->dispatchChannels($event, $recipient, $user);

            $this->broadcastArrival($recipient, $user);

            $delivered++;
        }

        return $delivered;
    }

    /**
     * Kênh chuông (DB) luôn bật. Các kênh cộng thêm (mail/push) theo ma trận
     * config/notification-policies.php — ngăn việc gửi email tràn lan cho
     * admin với các event mang tính thông tin.
     */
    private function dispatchChannels(NotificationEvent $event, NotificationRecipient $recipient, User $user): void
    {
        $channels = $this->channelsFor($event, $user);

        if (in_array('mail', $channels, true) && filled($user->email)) {
            $recipient->setRelation('event', $event);
            $recipient->setRelation('user', $user);

            Mail::to($user->email)->queue(new DynamicNotificationMail($recipient));
        }

        if (in_array('push', $channels, true)) {
            SendFcmNotificationJob::dispatch($recipient->getKey());
        }
    }

    /**
     * @return string[]
     */
    private function channelsFor(NotificationEvent $event, User $user): array
    {
        $eventType = strtoupper((string) $event->event_type);
        $role = strtoupper((string) $user->role);

        $perRole = config("notification-policies.channels.{$eventType}.{$role}");
        $fallback = config("notification-policies.channels.{$eventType}.*", config('notification-policies.channels.default', ['mail']));

        $extra = is_array($perRole) ? $perRole : (is_array($fallback) ? $fallback : []);

        // Chuông luôn được gửi; hợp nhất, bỏ trùng, chuẩn hoá.
        return array_values(array_unique(array_merge(['database'], $extra)));
    }

    /**
     * Cập nhật badge realtime ngay khi có recipient mới — không cần người dùng F5.
     * Gửi kèm nội dung thẻ để client có thể hiện ngay (prepend) thay vì chỉ tăng số.
     */
    private function broadcastArrival(NotificationRecipient $recipient, User $user): void
    {
        try {
            broadcast(new NotificationCountUpdated(
                $user->getKey(),
                $user->notificationRecipients()->whereNull('read_at')->count(),
                [
                    'id' => $recipient->getKey(),
                    'title' => $recipient->rendered_title,
                    'message' => $recipient->rendered_body,
                    'url' => $recipient->action_url
                        ?? route('notifications.show', ['notification' => $recipient->getKey()]),
                    'read_at' => null,
                    'time' => $recipient->created_at?->diffForHumans(),
                ]
            ))->toOthers();
        } catch (\Throwable $e) {
            // Reverb chưa chạy / lỗi broadcast không được làm hỏng việc gửi chính.
            Log::debug('Broadcast thông báo thất bại (không ảnh hưởng DB)', [
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
