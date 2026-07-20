<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->enum('review_level', ['STANDARD', 'ENHANCED'])
                ->default('STANDARD')
                ->after('face_match_score');
            $table->index(['status', 'review_level']);
        });
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropIndex(['status', 'review_level']);
            $table->dropColumn('review_level');
        });
    }
};
