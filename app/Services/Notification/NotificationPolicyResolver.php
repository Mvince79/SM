<?php

namespace App\Services\Notification;

use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use App\Services\Notification\Strategies\ConfigDrivenStrategy;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class NotificationPolicyResolver
{
    public function __construct(private readonly Container $container) {}

    /**
     * Chọn và chạy chính sách render cho một (event_type, role).
     *
     * Thứ tự tra cứu:
     *  1. strategies.{EVENT}.{ROLE}  → class riêng (logic phức tạp)
     *  2. content.{EVENT}.{ROLE}     → khai báo config
     *  3. content.{EVENT}.*          → khai báo dùng chung mọi role
     *  4. nội dung dự phòng          → không bao giờ để job chết vì thiếu khai báo
     *
     * @return array{title: string, body: string, action_url: string|null}
     */
    public function resolve(NotificationEvent $event, User $recipient): array
    {
        $eventType = strtoupper((string) $event->event_type);
        $role = strtoupper((string) $recipient->role);

        $strategyClass = config("notification-policies.strategies.{$eventType}.{$role}");

        if (is_string($strategyClass)) {
            $strategy = $this->container->make($strategyClass);

            if (! $strategy instanceof NotificationStrategy) {
                throw new UnexpectedValueException("Notification strategy [{$strategyClass}] phải implement NotificationStrategy.");
            }

            return $strategy->render($event, $recipient);
        }

        $content = config("notification-policies.content.{$eventType}.{$role}")
            ?? config("notification-policies.content.{$eventType}.*");

        if (is_array($content)) {
            return $this->container->make(ConfigDrivenStrategy::class)
                ->withContent($content)
                ->render($event, $recipient);
        }

        // Không ném exception: một loại thông báo mới bị quên khai báo không được
        // phép làm chết cả job và chặn những người nhận còn lại. Ghi log để lộ
        // ra khi kiểm thử, còn người dùng vẫn nhận được thông báo tối thiểu.
        Log::warning('Thiếu khai báo nội dung thông báo', [
            'event_type' => $eventType,
            'role' => $role,
        ]);

        return [
            'title' => 'Bạn có cập nhật mới',
            'body' => 'Có cập nhật mới liên quan đến hồ sơ bạn đang theo dõi. Bấm để xem chi tiết.',
            'action_url' => null,
        ];
    }
}
