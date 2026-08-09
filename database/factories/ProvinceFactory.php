<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        static $index = 0;
        $index++;

        return [
            'name' => 'Tỉnh ' . $index,
            'code' => 'VN-' . str_pad($index, 3, '0', STR_PAD_LEFT),
            'short_code' => 'PRV' . $index,
            'region' => $this->faker->randomElement(['BAC', 'TRUNG', 'NAM']),
            'is_active' => 1,
        ];
    }
}