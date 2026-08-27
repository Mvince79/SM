<?php

namespace Tests\Feature;

use App\Services\FaceMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceMatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_healthy_calls_health_endpoint_with_internal_key(): void
    {
        config([
            'face_match.service_url' => 'http://ai.test',
            'face_match.internal_key' => 'test-key',
        ]);

        Http::fake([
            'ai.test/health' => Http::response(['status' => 'ok', 'model' => 'ArcFace']),
        ]);

        $this->assertTrue(app(FaceMatchService::class)->isHealthy());

        Http::assertSent(fn (Request $request) => $request->url() === 'http://ai.test/health'
            && $request->hasHeader('X-Internal-Key', 'test-key'));
    }

    public function test_encode_returns_vector_from_ai_service(): void
    {
        config([
            'face_match.service_url' => 'http://ai.test',
            'face_match.internal_key' => 'test-key',
        ]);

        Storage::fake('public');
        Storage::disk('public')->putFileAs(
            'missing',
            UploadedFile::fake()->image('face.jpg', 100, 100),
            'face.jpg'
        );

        Http::fake([
            'ai.test/encode' => Http::response([
                'has_face' => true,
                'encoding' => array_fill(0, 512, 0.25),
                'model' => 'ArcFace',
            ]),
        ]);

        $result = app(FaceMatchService::class)->encode('missing/face.jpg');

        $this->assertTrue($result['has_face']);
        $this->assertCount(512, $result['encoding']);
        $this->assertSame('ArcFace', $result['model']);
    }

    public function test_compare_returns_score_from_ai_service(): void
    {
        config([
            'face_match.service_url' => 'http://ai.test',
            'face_match.internal_key' => 'test-key',
        ]);

        Http::fake([
            'ai.test/compare' => Http::response([
                'score' => 0.8735,
                'is_matched' => true,
                'model' => 'ArcFace',
            ]),
        ]);

        $result = app(FaceMatchService::class)->compare(
            array_fill(0, 512, 1.0),
            array_fill(0, 512, 1.0),
        );

        $this->assertSame(0.8735, $result['score']);
        $this->assertTrue($result['is_matched']);
        $this->assertSame('ArcFace', $result['model']);
    }

    public function test_verify_images_compares_id_document_with_selfie(): void
    {
        config([
            'face_match.service_url' => 'http://ai.test',
            'face_match.internal_key' => 'test-key',
        ]);

        Storage::fake('private');
        Storage::disk('private')->putFileAs(
            'kyc',
            UploadedFile::fake()->image('id.jpg', 100, 100),
            'id.jpg'
        );
        Storage::disk('private')->putFileAs(
            'kyc',
            UploadedFile::fake()->image('selfie.jpg', 100, 100),
            'selfie.jpg'
        );

        Http::fake([
            'ai.test/verify' => Http::response([
                'score' => 0.9132,
                'is_matched' => true,
                'model' => 'ArcFace',
            ]),
        ]);

        $result = app(FaceMatchService::class)->verifyImages(
            'kyc/id.jpg',
            'kyc/selfie.jpg',
        );

        $this->assertTrue($result['has_face']);
        $this->assertSame(0.9132, $result['score']);
        $this->assertTrue($result['is_matched']);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://ai.test/verify'
            && $request->hasHeader('X-Internal-Key', 'test-key'));
    }

    public function test_verify_images_returns_zero_when_ai_cannot_detect_a_face(): void
    {
        config(['face_match.service_url' => 'http://ai.test']);

        Storage::fake('private');
        Storage::disk('private')->putFileAs('kyc', UploadedFile::fake()->image('id.jpg', 10, 10), 'id.jpg');
        Storage::disk('private')->putFileAs('kyc', UploadedFile::fake()->image('selfie.jpg', 10, 10), 'selfie.jpg');

        Http::fake([
            'ai.test/verify' => Http::response(['detail' => 'no_face_detected'], 422),
        ]);

        $result = app(FaceMatchService::class)->verifyImages(
            'kyc/id.jpg',
            'kyc/selfie.jpg',
        );

        $this->assertFalse($result['has_face']);
        $this->assertSame(0.0, $result['score']);
        $this->assertFalse($result['is_matched']);
    }
}
