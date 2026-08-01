<?php

namespace Tests\Unit;

use App\Services\Chatbot\DataPrivacyIntent;
use App\Services\Chatbot\FaceMatchAccuracyIntent;
use App\Services\Chatbot\KycRequirementIntent;
use App\Services\Chatbot\ReportMissingChildIntent;
use App\Services\Chatbot\SupportTicketIntent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChatbotKnowledgeIntentTest extends TestCase
{
    #[DataProvider('matchingQuestions')]
    public function test_intent_matches_expected_questions(string $intentClass, string $question): void
    {
        $intent = new $intentClass;

        $this->assertTrue($intent->matches($question));
        $this->assertNotEmpty($intent->handle(['lat' => 0.0, 'lng' => 0.0])['message']);
    }

    public function test_intents_do_not_match_an_unrelated_question(): void
    {
        foreach ($this->intentClasses() as $intentClass) {
            $this->assertFalse((new $intentClass)->matches('Thời tiết hôm nay thế nào?'));
        }
    }

    public static function matchingQuestions(): array
    {
        return [
            'kyc' => [KycRequirementIntent::class, 'KYC cần giấy tờ gì?'],
            'kyc_without_accents' => [KycRequirementIntent::class, 'xac thuc danh tinh bang CMND duoc khong'],
            'face_match' => [FaceMatchAccuracyIntent::class, 'AI đối chiếu khuôn mặt có chính xác không?'],
            'privacy' => [DataPrivacyIntent::class, 'Số điện thoại có lộ không?'],
            'report_missing' => [ReportMissingChildIntent::class, 'Tôi muốn báo tin mất tích'],
            'support' => [SupportTicketIntent::class, 'Làm sao để liên hệ admin hỗ trợ?'],
        ];
    }

    /** @return list<class-string> */
    private function intentClasses(): array
    {
        return [
            KycRequirementIntent::class,
            FaceMatchAccuracyIntent::class,
            DataPrivacyIntent::class,
            ReportMissingChildIntent::class,
            SupportTicketIntent::class,
        ];
    }
}
