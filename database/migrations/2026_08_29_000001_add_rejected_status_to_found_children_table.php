<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sửa lỗi: nút "Từ chối" tin tìm thấy trẻ (FoundChildResource) đang ghi
 * status = 'REJECTED' và rejection_reason, nhưng:
 *  - enum status chỉ có PENDING/APPROVED/MATCHED/CLOSED → với strict mode = true
 *    (config/database.php) câu UPDATE ném QueryException, admin gặp lỗi 500;
 *  - cột rejection_reason chưa từng tồn tại → lý do admin nhập bị bỏ đi, dù
 *    helper text hứa "Lý do sẽ được gửi cho người báo tin".
 *
 * Bổ sung cả hai để luồng từ chối chạy được và người báo tin nhận được lý do.
 *
 * Lưu ý: SQLite (dùng cho test) không có ENUM và không hỗ trợ MODIFY COLUMN —
 * ở đó cột status vốn là TEXT nên không cần đổi gì, chỉ bỏ qua bước ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE found_children MODIFY COLUMN status "
                . "ENUM('PENDING','APPROVED','MATCHED','REJECTED','CLOSED') NOT NULL DEFAULT 'PENDING'"
            );
        }

        if (! Schema::hasColumn('found_children', 'rejection_reason')) {
            Schema::table('found_children', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Đưa REJECTED về CLOSED trước khi thu hẹp enum, tránh mất dữ liệu.
            DB::table('found_children')->where('status', 'REJECTED')->update(['status' => 'CLOSED']);

            DB::statement(
                "ALTER TABLE found_children MODIFY COLUMN status "
                . "ENUM('PENDING','APPROVED','MATCHED','CLOSED') NOT NULL DEFAULT 'PENDING'"
            );
        }

        if (Schema::hasColumn('found_children', 'rejection_reason')) {
            Schema::table('found_children', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
