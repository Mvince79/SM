<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cột NULL nên không cần backfill dữ liệu cũ
        Schema::table('missing_children', function (Blueprint $table) {
            $table->foreignId('handover_point_id')->nullable()
                ->after('id')
                ->constrained('handover_points')->nullOnDelete();
            $table->timestamp('handover_confirmed_at')->nullable();
            $table->foreignId('handover_confirmed_by')->nullable()
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('found_children', function (Blueprint $table) {
            $table->foreignId('handover_point_id')->nullable()
                ->after('id')
                ->constrained('handover_points')->nullOnDelete();
            $table->timestamp('handover_confirmed_at')->nullable();
            $table->foreignId('handover_confirmed_by')->nullable()
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropForeign(['handover_point_id']);
            $table->dropForeign(['handover_confirmed_by']);
            $table->dropColumn(['handover_point_id', 'handover_confirmed_at', 'handover_confirmed_by']);
        });

        Schema::table('found_children', function (Blueprint $table) {
            $table->dropForeign(['handover_point_id']);
            $table->dropForeign(['handover_confirmed_by']);
            $table->dropColumn(['handover_point_id', 'handover_confirmed_at', 'handover_confirmed_by']);
        });
    }
};
