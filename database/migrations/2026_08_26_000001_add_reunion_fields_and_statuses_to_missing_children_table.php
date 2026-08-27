<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * H-01 — Mở rộng ENUM trạng thái + cột xác nhận đoàn tụ cho missing_children.
     *
     * Thêm 3 trạng thái mới:
     *  - HANDED_OVER        : đã bàn giao cho cơ quan chức năng
     *  - REUNITED           : đã đoàn tụ thành công
     *  - UNREACHABLE_FAMILY : không liên lạc được với gia đình
     *
     * Thêm 3 cột xác nhận đoàn tụ (mốc "đã đoàn tụ" tách biệt với mốc "đã bàn giao"
     * vốn dùng các cột handover_point_id / handover_confirmed_at / handover_confirmed_by
     * đã có từ trước).
     */
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->timestamp('reunited_confirmed_at')->nullable()->after('handover_confirmed_by');
            $table->foreignId('reunited_confirmed_by')->nullable()->after('reunited_confirmed_at')
                ->constrained('users')->nullOnDelete();
            $table->enum('reunited_verification_method', ['FAMILY_SELF', 'ADMIN_PHONE_CALL'])
                ->nullable()->after('reunited_confirmed_by');
        });

        // Mở rộng ENUM status (giữ nguyên 6 giá trị cũ + thêm 3 giá trị mới).
        // Dùng MODIFY như migration expiration lifecycle trước đó để tương thích MySQL.
        // Trên SQLite (phpunit in-memory) status đã là text nên bỏ qua alter này.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_children MODIFY status ENUM('PENDING', 'APPROVED', 'FOUND', 'CLOSED', 'REJECTED', 'ARCHIVED', 'HANDED_OVER', 'REUNITED', 'UNREACHABLE_FAMILY') NOT NULL DEFAULT 'PENDING'");
        }
    }

    public function down(): void
    {
        // Trả status về 6 giá trị cũ (chỉ MySQL — SQLite không có ENUM).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_children MODIFY status ENUM('PENDING', 'APPROVED', 'FOUND', 'CLOSED', 'REJECTED', 'ARCHIVED') NOT NULL DEFAULT 'PENDING'");
        }

        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropForeign(['reunited_confirmed_by']);
            $table->dropColumn([
                'reunited_confirmed_at',
                'reunited_confirmed_by',
                'reunited_verification_method',
            ]);
        });
    }
};
