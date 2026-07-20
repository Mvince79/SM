<?php

namespace Tests\Feature;

use App\Exceptions\AIServiceUnavailableException;
use App\Models\KycVerification;
use App\Services\EkycService;
use App\Services\FaceMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EkycIdentityDocumentRecognitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fpt_ai_recognizes_old_nine_digit_identity_card(): void
    {
        $this->fakeFptResponse([
            'id' => '123 456 789',
            'name' => 'NGUYỄN VĂN AN',
            'dob' => '01/02/2000',
            'type' => 'old',
            'type_new' => 'cmnd_09_front',
        ]);

        $result = $this->service()->recognizeIdentityDocument($this->kyc());

        $this->assertSame('fpt_ai', $result['provider']);
        $this->assertSame('123456789', $result['id_number']);
        $this->assertSame('CMND_9', $result['id_type']);
        $this->assertSame('NGUYỄN VĂN AN', $result['full_name']);
        $this->assertSame('2000-02-01', $result['dob']);
    }

    public function test_fpt_ai_recognizes_twelve_digit_citizen_identity_card(): void
    {
        $this->fakeFptResponse([
            'id' => '001200012345',
            'name' => 'TRẦN THỊ BÌNH',
            'dob' => '1998-12-31',
            'type' => 'new',
            'type_new' => 'cccd_12_front',
        ]);

        $result = $this->service()->recognizeIdentityDocument($this->kyc());

        $this->assertSame('001200012345', $result['id_number']);
        $this->assertSame('CCCD_12', $result['id_type']);
        $this->assertSame('1998-12-31', $result['dob']);
    }

    public function test_vnpt_result_falls_back_to_number_length_when_type_is_missing(): void
    {
        config()->set('services.ekyc.provider', 'vnpt');
        config()->set('services.ekyc.vnpt.token_id', 'token-id');
        config()->set('services.ekyc.vnpt.token_key', 'token-key');
        config()->set('services.ekyc.vnpt.base_url', 'https://vnpt.test');
        Http::fake([
            'https://vnpt.test/*' => Http::response([
                'object' => [
                    'id' => '123456789',
                    'name' => 'LÊ VĂN C',
                    'birth_day' => '03-04-1999',
                ],
            ]),
        ]);

        $result = $this->service()->recognizeIdentityDocument($this->kyc());

        $this->assertSame('CMND_9', $result['id_type']);
        $this->assertSame('1999-04-03', $result['dob']);
    }

    public function test_fpt_ai_application_error_is_not_treated_as_valid_ocr(): void
    {
        config()->set('services.ekyc.provider', 'fpt_ai');
        config()->set('services.ekyc.fpt_ai.api_key', 'api-key');
        config()->set('services.ekyc.fpt_ai.base_url', 'https://fpt.test');
        Http::fake([
            'https://fpt.test/*' => Http::response([
                'errorCode' => 3,
                'errorMessage' => 'Unable to find ID card in the image',
                'data' => [],
            ]),
        ]);

        $this->expectException(AIServiceUnavailableException::class);

        $this->service()->recognizeIdentityDocument($this->kyc());
    }

    private function fakeFptResponse(array $data): void
    {
        config()->set('services.ekyc.provider', 'fpt_ai');
        config()->set('services.ekyc.fpt_ai.api_key', 'api-key');
        config()->set('services.ekyc.fpt_ai.base_url', 'https://fpt.test');
        Http::fake([
            'https://fpt.test/*' => Http::response([
                'errorCode' => 0,
                'errorMessage' => '',
                'data' => [$data],
            ]),
        ]);
    }

    private function kyc(): KycVerification
    {
        Storage::fake('private');
        Storage::disk('private')->put('kyc/front.jpg', 'fake-image');

        return new KycVerification(['front_image_path' => 'kyc/front.jpg']);
    }

    private function service(): EkycService
    {
        return new EkycService(Mockery::mock(FaceMatchService::class));
    }
}
