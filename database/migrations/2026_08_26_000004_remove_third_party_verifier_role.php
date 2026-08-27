<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * G-01 — Gỡ bỏ hoàn toàn role THIRD_PARTY_VERIFIER.
     *
     * Chuyển user mang role cũ sang USER trước khi gỡ giá trị khỏi ENUM (tránh
     * lỗi ALTER khi còn row tham chiếu). Giữ nguyên bảng third_party_verifier_profiles
     * ở trạng thái deprecated (không xóa dữ liệu).
     *
     * Trên SQLite (phpunit in-memory) role đã là string nên chỉ cần ngừng reference ở code.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('users')
                ->where('role', 'THIRD_PARTY_VERIFIER')
                ->update(['role' => 'USER']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'ADMIN',
                'MODERATOR',
                'VOLUNTEER',
                'USER'
            ) NOT NULL DEFAULT 'USER'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'ADMIN',
                'MODERATOR',
                'VOLUNTEER',
                'USER',
                'THIRD_PARTY_VERIFIER'
            ) NOT NULL DEFAULT 'USER'");
        }
    }
};
