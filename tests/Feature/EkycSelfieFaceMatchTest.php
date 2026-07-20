<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Services\EkycService;
use App\Services\FaceMatchService;
use Mockery;
use Tests\TestCase;

class EkycSelfieFaceMatchTest extends TestCase
{
    public function test_matches_selfie_with_identity_document_through_ai_service(): void
    {
        $kyc = new KycVerification([
            'front_image_path' => 'kyc/id.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
        ]);

        $faceMatch = Mockery::mock(FaceMatchService::class);
        $faceMatch->shouldReceive('isHealthy')->once()->andReturnTrue();
        $faceMatch->shouldReceive('verifyImages')
            ->once()
            ->with('kyc/id.jpg', 'kyc/selfie.jpg', 'private')
            ->andReturn([
                'has_face' => true,
                'score' => 0.91,
                'is_matched' => true,
                'model' => 'ArcFace',
            ]);

        $score = (new EkycService($faceMatch))->matchSelfieWithId($kyc);

        $this->assertSame(0.91, $score);
    }

    public function test_returns_zero_when_either_image_has_no_face(): void
    {
        $kyc = new KycVerification([
            'front_image_path' => 'kyc/id.jpg',
            'selfie_path' => 'kyc/selfie.jpg',
        ]);

        $faceMatch = Mockery::mock(FaceMatchService::class);
        $faceMatch->shouldReceive('isHealthy')->once()->andReturnTrue();
        $faceMatch->shouldReceive('verifyImages')->once()->andReturn([
            'has_face' => false,
            'score' => 0.0,
            'is_matched' => false,
            'model' => 'ArcFace',
        ]);

        $score = (new EkycService($faceMatch))->matchSelfieWithId($kyc);

        $this->assertSame(0.0, $score);
    }
}
