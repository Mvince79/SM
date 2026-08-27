<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->timestamp('reunion_reminder_sent_at')->nullable()->after('reunited_verification_method');
        });
    }

    public function down(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropColumn(['reunion_reminder_sent_at']);
        });
    }
};
