<?php

namespace Database\Factories;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\Concerns\GeneratesVietnameseDemoData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MissingChild>
 */
class MissingChildFactory extends Factory
{
    use GeneratesVietnameseDemoData;

    protected $model = MissingChild::class;

    public function definition(): array
    {
        // Dùng dữ liệu tiếng Việt thực tế (tên, địa chỉ...) thay vì Faker mặc định
        // tiếng Anh, để dữ liệu demo/test không hiển thị tên/địa chỉ ngoại lai.
        $gender = $this->faker->randomElement(['MALE', 'FEMALE']);
        $fullName = $this->randomFullName($gender);

        $provinceId = Province::query()->inRandomOrder()->value('id');
        $provinces = $this->demoProvinces();
        $area = $provinceId && isset($provinces[$provinceId])
            ? $provinces[$provinceId]['areas'][array_rand($provinces[$provinceId]['areas'])]
            : $provinces[1]['areas'][0]; // fallback: Hà Nội

        $caseCode = 'MC-' . now()->format('Y') . '-' . str_pad(
            (string) $this->faker->unique()->numberBetween(1, 99999),
            5,
            '0',
            STR_PAD_LEFT
        );

        return [
            'user_id' => User::query()->inRandomOrder()->value('id')
                ?? User::factory(),

            'case_code' => $caseCode,

            'slug' => Str::slug($fullName) . '-' . strtolower($caseCode),

            'full_name' => $fullName,

            'dob' => $this->faker->dateTimeBetween('-17 years', '-2 years')->format('Y-m-d'),

            'age_estimate' => $this->faker->numberBetween(2, 17),

            'gender' => $gender,

            // Enum xac nhan tu migration 2026_05_14_000004_create_missing_children_table.php
            'skin_tone' => $this->faker->randomElement(['LIGHT', 'MEDIUM', 'DARK']),

            'height_cm' => $this->faker->numberBetween(80, 180),

            'weight_kg' => $this->faker->randomFloat(2, 15, 80),

            'province_id' => $provinceId ?? Province::factory(),

            'ward' => $area['ward'],

            'address_detail' => 'Gần ' . $area['ward'],

            'lat' => $area['lat'] + (random_int(-30, 30) / 10000),

            'lng' => $area['lng'] + (random_int(-30, 30) / 10000),

            'missing_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),

            'missing_time' => $this->faker->time('H:i'),

            'clothes_desc' => $this->clothesDescPool()[array_rand($this->clothesDescPool())],

            'clothes_color' => $this->faker->safeColorName(),

            'special_marks' => $this->specialMarksPool()[array_rand($this->specialMarksPool())],

            'circumstances' => $this->circumstancesPool()[array_rand($this->circumstancesPool())],

            'reward_info' => null,

            'contact_name' => $this->randomAdultName($gender === 'MALE' ? 'FEMALE' : 'MALE'),

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