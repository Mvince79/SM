<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Việc 2 — Thêm cột snapshot trạng thái trước khi chuyển sang HANDED_OVER,
     * đồng bộ với missing_child_prev_status / found_child_prev_status trên bảng
     * face_match_results (migration 2026_08_30_000001 / 000002).
     *
     * Dùng để luồng "Gia đình phủ nhận kết quả khớp" (denyHandover) revert
     * MissingChild về đúng trạng thái công khai trước đó (FOUND/APPROVED) thay vì
     * hardcode. KHÔNG đổi tên/kiểu bất kỳ cột nào khác.
     */
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->string('missing_child_prev_status', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropColumn('missing_child_prev_status');
        });
    }
};
