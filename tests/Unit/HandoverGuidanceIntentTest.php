<?php

namespace Tests\Unit;

use App\Services\Chatbot\HandoverGuidanceIntent;
use App\Services\HandoverPointFinderService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HandoverGuidanceIntentTest extends TestCase
{
    #[DataProvider('handoverQuestions')]
    public function test_matches_handover_guidance_questions(string $message): void
    {
        $finder = Mockery::mock(HandoverPointFinderService::class);
        $intent = new HandoverGuidanceIntent($finder);

        $this->assertTrue($intent->matches($message));
    }

    public function test_does_not_match_unrelated_question(): void
    {
        $finder = Mockery::mock(HandoverPointFinderService::class);
        $intent = new HandoverGuidanceIntent($finder);

        $this->assertFalse($intent->matches('Tôi muốn tìm kiếm hồ sơ trẻ thất lạc'));
    }

    public static function handoverQuestions(): array
    {
        return [
            ['Tôi nên đưa bé đến đâu?'],
            ['Có thể bàn giao trẻ ở đâu?'],
            ['Cho tôi địa chỉ điểm tiếp nhận trẻ gần nhất'],
        ];
    }
}
