<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chatbot_sessions MODIFY COLUMN intent ENUM(
                'REPORT_MISSING',
                'REPORT_FOUND',
                'SEARCH',
                'GENERAL',
                'HANDOVER_GUIDANCE'
            ) NULL");

            return;
        }

        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->string('intent', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('chatbot_sessions')
            ->where('intent', 'HANDOVER_GUIDANCE')
            ->update(['intent' => 'GENERAL']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chatbot_sessions MODIFY COLUMN intent ENUM(
                'REPORT_MISSING',
                'REPORT_FOUND',
                'SEARCH',
                'GENERAL'
            ) NULL");

            return;
        }

        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->string('intent', 30)->nullable()->change();
        });
    }
};
