<?php

namespace App\Services\Chatbot;

use App\Services\Chatbot\Contracts\ChatbotIntent;

class ChatbotIntentResolver
{
    /** @var list<ChatbotIntent> */
    private array $intents;

    public function __construct(
        HandoverGuidanceIntent $handoverGuidanceIntent,
        KycRequirementIntent $kycRequirementIntent,
        FaceMatchAccuracyIntent $faceMatchAccuracyIntent,
        DataPrivacyIntent $dataPrivacyIntent,
        ReportMissingChildIntent $reportMissingChildIntent,
        SupportTicketIntent $supportTicketIntent,
    ) {
        $this->intents = [
            $handoverGuidanceIntent,
            $faceMatchAccuracyIntent,
            $dataPrivacyIntent,
            $kycRequirementIntent,
            $reportMissingChildIntent,
            $supportTicketIntent,
        ];
    }

    /**
     * @param  array{lat: float, lng: float}  $context
     * @return array<string, mixed>
     */
    public function resolve(string $message, array $context): array
    {
        foreach ($this->intents as $intent) {
            if ($intent->matches($message)) {
                return [
                    'intent' => $intent->name(),
                    ...$intent->handle($context),
                ];
            }
        }

        $suggestions = $this->suggestionsFor($message);

        // Nhánh này chỉ đạt tới khi Gemini trả null (quá tải/429/mất mạng) VÀ không
        // khớp từ khóa nào. Không nói "tôi không hiểu" một cách bế tắc — báo AI tạm
        // bận, hướng hotline khẩn cấp và gợi ý câu hỏi đúng.
        return [
            'intent' => 'GENERAL',
            'message' => "Hiện tại trợ lý AI tạm thời quá tải hoặc mất kết nối, nên mình chưa xử lý được câu này. "
                ."Bạn vui lòng thử lại sau ít phút. Trường hợp khẩn cấp, hãy gọi ngay Tổng đài Quốc gia Bảo vệ Trẻ em 111 (miễn phí) hoặc Công an 113.\n\n"
                ."Bạn cũng có thể thử hỏi:\n- ".implode("\n- ", $suggestions),
            'handover_points' => [],
            'suggested_questions' => $suggestions,
        ];
    }

    /** @return list<string> */
    private function suggestionsFor(string $message): array
    {
        $questions = [
            'Tôi nên đưa bé đến đâu?',
            'KYC cần giấy tờ gì?',
            'AI đối chiếu khuôn mặt có chính xác không?',
            'Dữ liệu cá nhân của tôi được bảo vệ thế nào?',
            'Tôi đăng tin trẻ mất tích như thế nào?',
            'Làm sao để gửi yêu cầu hỗ trợ?',
        ];
        $start = (int) sprintf('%u', crc32($message)) % count($questions);
        $rotated = [...array_slice($questions, $start), ...array_slice($questions, 0, $start)];

        return array_slice($rotated, 0, 3);
    }
}
