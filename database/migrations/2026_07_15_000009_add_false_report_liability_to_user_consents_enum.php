<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Xác nhận đúng theo migration thật:
     *   - 2026_07_07_000002_create_user_consents_table.php:
     *     ENUM('TOS', 'PRIVACY_POLICY', 'CHILD_DATA_POLICY', 'KYC_CONSENT')
     * → giá trị hiện tại: TOS, PRIVACY_POLICY, CHILD_DATA_POLICY, KYC_CONSENT
     *
     * Dùng driver-check giống migration 2026_07_07_000005 đã có sẵn trong repo,
     * vì `php artisan test` chạy trên SQLite in-memory — không hỗ trợ cú pháp
     * ALTER TABLE ... MODIFY COLUMN (chỉ MySQL mới có).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_consents MODIFY COLUMN consent_type ENUM(
                'TOS',
                'PRIVACY_POLICY',
                'CHILD_DATA_POLICY',
                'KYC_CONSENT',
                'FALSE_REPORT_LIABILITY'
            ) NOT NULL");
        } else {
            Schema::table('user_consents', function (Blueprint $table) {
                $table->string('consent_type', 40)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_consents MODIFY COLUMN consent_type ENUM(
                'TOS',
                'PRIVACY_POLICY',
                'CHILD_DATA_POLICY',
                'KYC_CONSENT'
            ) NOT NULL");
        } else {
            Schema::table('user_consents', function (Blueprint $table) {
                $table->string('consent_type', 40)->change();
            });
        }
    }
};