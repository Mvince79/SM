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
}
