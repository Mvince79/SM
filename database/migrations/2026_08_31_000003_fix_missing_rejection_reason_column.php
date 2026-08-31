<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: đảm bảo cột `rejection_reason` THỰC SỰ tồn tại trên `found_children`.
 *
 * Migration 2026_08_31_000002 cùng mục đích đã bị ghi nhận là "đã chạy" trong
 * bảng `migrations` (do từng restore DB từ bản dump cũ hoặc chạy trước đó), nên
 * `php artisan migrate` báo "Nothing to migrate" và KHÔNG thêm lại cột. Kết quả
 * là nút "Từ chối" tin tìm thấy vẫn ném lỗi SQL 1054 Unknown column.
 *
 * Migration này dùng tên MỚI (chưa từng được ghi nhận) nên `migrate` sẽ bắt buộc
 * thực thi nó. Logic IDEMPOTENT:
 *   - Chỉ MODIFY ENUM status thêm REJECTED nếu cần (vô hại nếu đã có).
 *   - Chỉ thêm cột `rejection_reason` khi THỰC SỰ thiếu.
 * Chạy lại nhiều lần không phá dữ liệu, không lỗi trùng cột.
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
