<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Thêm intent 'GEMINI' vào ENUM của chatbot_sessions.intent.
 *
 * Nguyên nhân: ChatbotGeminiService trả intent = 'GEMINI' (cuộc hội thoại do AI xử lý),
 * nhưng ENUM cũ chỉ chứa 10 intent luật cứng → INSERT báo
 * "Data truncated for column 'intent'" → 500. Mở rộng ENUM để chứa 'GEMINI'.
 */
return new class extends Migration
{
    private const INTENTS = [
        'REPORT_MISSING',
        'REPORT_FOUND',
        'SEARCH',
        'GENERAL',
        'HANDOVER_GUIDANCE',
        'KYC_REQUIREMENT',
        'FACE_MATCH_ACCURACY',
        'DATA_PRIVACY',
        'REPORT_MISSING_CHILD',
        'SUPPORT_TICKET',
        'GEMINI',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE chatbot_sessions MODIFY COLUMN intent ENUM(%s) NULL',
            $this->enumValues(self::INTENTS),
        ));
    }

    public function down(): void
    {
        // Chuyển các phiên GEMINI về GENERAL trước khi thu hẹp ENUM.
        DB::table('chatbot_sessions')
            ->where('intent', 'GEMINI')
            ->update(['intent' => 'GENERAL']);

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE chatbot_sessions MODIFY COLUMN intent ENUM(%s) NULL',
            $this->enumValues(array_slice(self::INTENTS, 0, 10)),
        ));
    }

    /** @param list<string> $values */
    private function enumValues(array $values): string
    {
        return implode(', ', array_map(
            static fn (string $value): string => DB::connection()->getPdo()->quote($value),
            $values,
        ));
    }
};
