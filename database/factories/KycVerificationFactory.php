<?php

namespace Database\Factories;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycVerificationFactory extends Factory
{
    protected $model = KycVerification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_number' => (string) $this->faker->unique()->numerify('###############'), // 15 số giả CCCD — sẽ tự mã hóa qua Model mutator
            'full_name_on_id' => $this->faker->name(),
            'dob_on_id' => $this->faker->date(),
            'front_image_path' => 'kyc/test/front.jpg',
            'back_image_path' => 'kyc/test/back.jpg',
            'selfie_path' => 'kyc/test/selfie.jpg',
            'ocr_provider' => null,
            'ocr_raw_response' => null,
            'face_match_score' => null,
            'status' => 'PENDING',
            'reviewed_by' => null,
            'verified_at' => null,
            'rejected_reason' => null,
        ];
    }
}