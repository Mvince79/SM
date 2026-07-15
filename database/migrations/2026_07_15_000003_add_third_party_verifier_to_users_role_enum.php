<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Xác nhận đúng theo migration thật:
     *   - 2026_05_14_000002_create_users_table.php: ENUM('ADMIN','MODERATOR','USER')
     *   - 2026_07_07_000005_add_kyc_status_and_volunteer_role_to_users_table.php:
     *     MODIFY role ENUM('ADMIN','MODERATOR','VOLUNTEER','USER')
     * → giá trị hiện tại: ADMIN, MODERATOR, VOLUNTEER, USER
     *
     * Dùng driver-check giống migration 2026_07_07_000005 đã có sẵn trong repo,
     * vì `php artisan test` chạy trên SQLite in-memory — không hỗ trợ cú pháp
     * ALTER TABLE ... MODIFY COLUMN (chỉ MySQL mới có).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'ADMIN',
                'MODERATOR',
                'VOLUNTEER',
                'USER',
                'THIRD_PARTY_VERIFIER'
            ) NOT NULL DEFAULT 'USER'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 30)->default('USER')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'ADMIN',
                'MODERATOR',
                'VOLUNTEER',
                'USER'
            ) NOT NULL DEFAULT 'USER'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('USER')->change();
            });
        }
    }
};