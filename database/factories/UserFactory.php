<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'google_id' => null,
            'avatar_url' => null,
            'phone' => fake()->phoneNumber(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'role' => 'user',
            'status' => 'ACTIVE',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
            'status' => 'PENDING',
        ]);
    }
}