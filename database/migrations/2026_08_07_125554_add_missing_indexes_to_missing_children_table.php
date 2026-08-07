<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->index('review_level', 'idx_missing_review_level');

            $table->index(
                ['status', 'created_at'],
                'idx_missing_status_created_at'
            );

            $table->index(
                ['status', 'province_id'],
                'idx_missing_status_province'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropIndex('idx_missing_review_level');
            $table->dropIndex('idx_missing_status_created_at');
            $table->dropIndex('idx_missing_status_province');
        });
    }
};