<?php

namespace Database\Factories;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationRecipient>
 */
class NotificationRecipientFactory extends Factory
{
    protected $model = NotificationRecipient::class;

    public function definition(): array
    {
        return [
            'notification_event_id' => NotificationEvent::factory(),
            'user_id' => User::factory(),
            'role_at_time' => 'USER',
            'rendered_title' => $this->faker->sentence(4),
            'rendered_body' => $this->faker->paragraph(),
            'action_url' => '/missing/1',
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn() => ['read_at' => now()]);
    }
}