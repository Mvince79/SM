<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => $this->faker->randomElement(['BUG', 'COMPLAINT', 'FEEDBACK', 'FRAUD_SUSPECT']),
            'subject' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'status' => 'OPEN',
        ];
    }
}