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
            'user_id' => User::query()->inRandomOrder()->value('id')
                ?? User::factory(),

            'case_code' => 'MC-' . now()->format('Y') . '-' . str_pad(
                (string) $this->faker->unique()->numberBetween(1, 99999),
                5,
                '0',
                STR_PAD_LEFT
            ),

            'full_name' => $this->faker->name(),

            'dob' => $this->faker->dateTimeBetween('-17 years', '-2 years')->format('Y-m-d'),

            'age_estimate' => $this->faker->numberBetween(2, 17),

            'gender' => $this->faker->randomElement(['MALE', 'FEMALE']),

            // Enum xac nhan tu migration 2026_05_14_000004_create_missing_children_table.php
            'skin_tone' => $this->faker->randomElement(['LIGHT', 'MEDIUM', 'DARK']),

            'height_cm' => $this->faker->numberBetween(80, 180),

            'weight_kg' => $this->faker->randomFloat(2, 15, 80),

            'province_id' => Province::query()->inRandomOrder()->value('id')
                ?? Province::factory(),

            'district' => $this->faker->citySuffix(),

            'ward' => $this->faker->streetName(),

            'address_detail' => $this->faker->address(),

            'lat' => $this->faker->latitude(8.5, 23.5),

            'lng' => $this->faker->longitude(102.0, 110.0),

            'missing_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),

            'missing_time' => $this->faker->time('H:i'),

            'clothes_desc' => $this->faker->sentence(),

            'clothes_color' => $this->faker->safeColorName(),

            'special_marks' => $this->faker->optional()->sentence(),

            'circumstances' => $this->faker->paragraph(),

            'reward_info' => null,

            'contact_name' => $this->faker->name(),

            // Prefix '09' dam bao dung dinh dang so di dong VN (tranh sinh so khong hop le)
            'contact_phone' => $this->faker->numerify('09########'),

            // Enum xac nhan tu migration 2026_07_07_..._alter_contact_relation_missing_children.php
            // Default 'PARENT' — dung nguyen bo enum de test coverage da dang hon
            'contact_relation' => $this->faker->randomElement([
                'PARENT',
                'GRANDPARENT',
                'LEGAL_GUARDIAN',
                'SIBLING',
                'RELATIVE_OTHER',
                'WITNESS_NO_RELATION',
                'ORGANIZATION',
            ]),

            // Giu mac dinh PENDING de cac test khong truyen status khong bi flaky
            // (dung state approved()/found()/... ben duoi khi can trang thai khac)
            'status' => 'PENDING',

            // Cot co that tren missing_children, them tu migration 2026_07_07
            'review_level' => $this->faker->randomElement(['STANDARD', 'ENHANCED']),

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

    public function closed(): static
    {
        return $this->state(fn() => [
            'status' => 'CLOSED',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn() => [
            'status' => 'REJECTED',
        ]);
    }
}