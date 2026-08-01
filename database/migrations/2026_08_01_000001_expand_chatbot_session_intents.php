<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        DB::table('chatbot_sessions')
            ->whereIn('intent', [
                'KYC_REQUIREMENT',
                'FACE_MATCH_ACCURACY',
                'DATA_PRIVACY',
                'REPORT_MISSING_CHILD',
                'SUPPORT_TICKET',
            ])
            ->update(['intent' => 'GENERAL']);

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE chatbot_sessions MODIFY COLUMN intent ENUM(%s) NULL',
            $this->enumValues(array_slice(self::INTENTS, 0, 5)),
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
