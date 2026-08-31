<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đảm bảo cột `rejection_reason` tồn tại trên bảng `found_children`.
 *
 * Migration gốc 2026_08_29_000001 đã thêm cột này, nhưng trên một số DB cột
 * chưa được tạo (DB restore/rollback làm lệch trạng thái bảng migrations so với
 * cấu trúc thật) → nút "Từ chối" tin tìm thấy ném lỗi SQL 1054 Unknown column.
 * `php artisan migrate` báo "Nothing to migrate" vì migration gốc đã được ghi
 * nhận là chạy, nên ta tạo migration MỚI này để chủ động kiểm tra & thêm lại.
 *
 * Migration này IDEMPOTENT: chỉ thêm cột nếu thiếu, chỉ ALTER ENUM nếu cần —
 * chạy lại nhiều lần không phá dữ liệu, không trùng lặp.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Đảm bảo ENUM status có REJECTED (định nghĩa lại cùng ENUM là vô hại).
            DB::statement(
                'ALTER TABLE found_children MODIFY COLUMN status '
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
            DB::table('found_children')->where('status', 'REJECTED')->update(['status' => 'CLOSED']);
            DB::statement(
                'ALTER TABLE found_children MODIFY COLUMN status '
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
