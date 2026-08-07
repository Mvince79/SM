<?php

namespace Database\Factories;

use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationEvent>
 */
class NotificationEventFactory extends Factory
{
    protected $model = NotificationEvent::class;

    public function definition(): array
    {
        return [
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => 'missing_children',
            'subject_id' => 1,
            'payload' => ['case_id' => 1],
            'triggered_by_user_id' => User::factory(),
            'occurred_at' => now(),
        ];
    }
}