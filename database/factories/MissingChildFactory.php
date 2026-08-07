<?php

namespace Database\Factories;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissingChild>
 */
class MissingChildFactory extends Factory
{
    protected $model = MissingChild::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'case_code' => 'MC-' . now()->format('Y') . '-' . str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'full_name' => $this->faker->name(),
            'dob' => $this->faker->dateTimeBetween('-15 years', '-1 years')->format('Y-m-d'),
            'age_estimate' => $this->faker->numberBetween(1, 15),
            'gender' => $this->faker->randomElement(['MALE', 'FEMALE']),
            'skin_tone' => $this->faker->randomElement(['TRANG', 'NGAM', 'DEN']),
            'height_cm' => $this->faker->numberBetween(60, 160),
            'weight_kg' => $this->faker->numberBetween(10, 60),
            'province_id' => Province::factory(),
            'district' => $this->faker->citySuffix(),
            'ward' => $this->faker->streetName(),
            'address_detail' => $this->faker->address(),
            'lat' => $this->faker->latitude(8, 23),
            'lng' => $this->faker->longitude(102, 109),
            'missing_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'missing_time' => $this->faker->time('H:i'),
            'clothes_desc' => $this->faker->sentence(),
            'clothes_color' => $this->faker->safeColorName(),
            'special_marks' => $this->faker->optional()->sentence(),
            'circumstances' => $this->faker->paragraph(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->numerify('09########'),
            'contact_relation' => $this->faker->randomElement(['Cha', 'Mẹ', 'Ông', 'Bà', 'Anh/Chị']),
            'status' => 'PENDING',
            'view_count' => 0,
            'share_count' => 0,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => [
            'status' => 'APPROVED',
            'approved_at' => now(),
        ]);
    }

    public function found(): static
    {
        return $this->state(fn() => [
            'status' => 'FOUND',
            'found_at' => now(),
        ]);
    }
}