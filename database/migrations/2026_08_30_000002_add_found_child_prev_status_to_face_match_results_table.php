<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lưu trạng thái của FoundChild TRƯỚC khi được đẩy sang MATCHED bởi luồng
 * xác nhận đối chiếu (FaceMatchResultsTable::confirm). Dùng để revert faithful
 * (đối xứng với missing_child_prev_status) khi MissingChild bị từ chối hoặc xoá:
 * trả FoundChild về đúng trạng thái trước đó thay vì hardcode APPROVED.
 *
 * Cột nullable, thêm được trên cả MySQL (production) và SQLite (test).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->string('found_child_prev_status', 30)->nullable()->after('missing_child_prev_status');
        });
    }

    public function down(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->dropColumn('found_child_prev_status');
        });
    }
};
