<?php

namespace Database\Factories;

use App\Models\HandoverPoint;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HandoverPoint>
 */
class HandoverPointFactory extends Factory
{
    protected $model = HandoverPoint::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' - Điểm tiếp nhận',
            'type' => $this->faker->randomElement(['POLICE_STATION', 'COMMUNE_PEOPLE_COMMITTEE', 'HOTLINE_111', 'NGO', 'OTHER']),
            'address' => $this->faker->address(),
            'province_id' => Province::factory(),
            'lat' => $this->faker->latitude(8, 23),
            'lng' => $this->faker->longitude(102, 109),
            'hotline_phone' => $this->faker->numerify('19########'),
            'responsible_org_name' => $this->faker->company(),
            'priority_order' => 0,
            'is_active' => 1,
        ];
    }
}