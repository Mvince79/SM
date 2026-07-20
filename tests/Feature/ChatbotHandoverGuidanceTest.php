<?php

namespace Tests\Feature;

use App\Models\ChatbotSession;
use App\Models\HandoverPoint;
use App\Services\HandoverPointFinderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ChatbotHandoverGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_handover_question_returns_nearest_points_and_logs_session(): void
    {
        $points = collect([
            $this->point(1, 'Công an phường Bến Nghé', 10.7756, 106.7019, 1.234),
            $this->point(2, 'UBND phường Tân Định', 10.7891, 106.6917, 2.456),
            $this->point(3, 'Làng trẻ em SOS Gò Vấp', 10.8386, 106.6650, 7.891),
        ]);

        $finder = Mockery::mock(HandoverPointFinderService::class);
        $finder->shouldReceive('findNearest')
            ->once()
            ->with(10.776, 106.7, 3)
            ->andReturn($points);
        $this->app->instance(HandoverPointFinderService::class, $finder);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Tôi nên đưa bé đến đâu?',
            'lat' => 10.776,
            'lng' => 106.7,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'HANDOVER_GUIDANCE')
            ->assertJsonPath('data.handover_points.0.name', 'Công an phường Bến Nghé')
            ->assertJsonPath('data.handover_points.0.distance_km', 1.23)
            ->assertJsonPath('data.hotline.phone', '111')
            ->assertJsonCount(3, 'data.handover_points');

        $session = ChatbotSession::query()->sole();

        $this->assertSame('HANDOVER_GUIDANCE', $session->intent);
        $this->assertSame('COMPLETED', $session->status);
        $this->assertSame('showed_handover_points', $session->completed_action);
        $this->assertSame('Tôi nên đưa bé đến đâu?', $session->collected_data['messages'][0]['content']);
        $this->assertSame(10.776, $session->collected_data['location']['lat']);
    }

    public function test_coordinates_are_required_for_handover_guidance(): void
    {
        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Tôi nên đưa bé đến đâu?',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);

        $this->assertDatabaseCount('chatbot_sessions', 0);
    }

    private function point(
        int $id,
        string $name,
        float $lat,
        float $lng,
        float $distance,
    ): HandoverPoint {
        return (new HandoverPoint)->forceFill([
            'id' => $id,
            'name' => $name,
            'type' => 'POLICE_STATION',
            'address' => 'Địa chỉ '.$id,
            'hotline_phone' => '028000000'.$id,
            'lat' => $lat,
            'lng' => $lng,
            'distance' => $distance,
        ]);
    }
}
