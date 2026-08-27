<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * C-01 — Thêm giá trị DATA_USE_LIMITATION vào enum consent_type.
     *
     * Theo pattern của migration 2026_07_15_000009: trên MySQL dùng ALTER ENUM,
     * trên SQLite (phpunit in-memory) consent_type đã là string(40) nên cho phép
     * mọi giá trị, không cần đổi schema.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_consents MODIFY COLUMN consent_type ENUM(
                'TOS',
                'PRIVACY_POLICY',
                'CHILD_DATA_POLICY',
                'KYC_CONSENT',
                'FALSE_REPORT_LIABILITY',
                'DATA_USE_LIMITATION'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_consents MODIFY COLUMN consent_type ENUM(
                'TOS',
                'PRIVACY_POLICY',
                'CHILD_DATA_POLICY',
                'KYC_CONSENT',
                'FALSE_REPORT_LIABILITY'
            ) NOT NULL");
        }
    }
};
