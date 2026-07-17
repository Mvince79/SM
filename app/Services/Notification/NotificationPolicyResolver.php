<?php

namespace App\Services\Notification;

use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use App\Services\Notification\Exceptions\NotificationStrategyNotFoundException;
use Illuminate\Contracts\Container\Container;
use UnexpectedValueException;

class NotificationPolicyResolver
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{title: string, body: string, action_url: string|null}
     */
    public function resolve(NotificationEvent $event, User $recipient): array
    {
        $eventType = strtoupper((string) $event->event_type);
        $role = strtoupper((string) $recipient->role);
        $strategyClass = config("notification-policies.strategies.{$eventType}.{$role}");

        if (! is_string($strategyClass)) {
            throw NotificationStrategyNotFoundException::for($eventType, $role);
        }

        $strategy = $this->container->make($strategyClass);

        if (! $strategy instanceof NotificationStrategy) {
            throw new UnexpectedValueException("Notification strategy [{$strategyClass}] phải implement NotificationStrategy.");
        }

        return $strategy->render($event, $recipient);
    }
}
