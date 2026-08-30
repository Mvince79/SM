<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lưu trạng thái của MissingChild TRƯỚC khi được đẩy sang FOUND bởi luồng
 * xác nhận đối chiếu (FaceMatchResultsTable::confirm). Dùng để revert faithful
 * (quyết định #1) khi FoundChild bị từ chối hoặc xoá: trả MissingChild về
 * đúng trạng thái trước đó thay vì đoán.
 *
 * Cột nullable, thêm được trên cả MySQL (production) và SQLite (test).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->string('missing_child_prev_status', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->dropColumn('missing_child_prev_status');
        });
    }
};
