<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sáp nhập hành chính VN 2025: bỏ cấp Huyện/Quận, chỉ còn 2 cấp Tỉnh → Xã.
     * Xóa cột `district` khỏi missing_children & found_children (nếu tồn tại).
     */
    public function up(): void
    {
        if (Schema::hasColumn('missing_children', 'district')) {
            Schema::table('missing_children', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }

        if (Schema::hasColumn('found_children', 'district')) {
            Schema::table('found_children', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('missing_children', 'district')) {
            Schema::table('missing_children', function (Blueprint $table) {
                $table->string('district', 100)->nullable();
            });
        }

        if (! Schema::hasColumn('found_children', 'district')) {
            Schema::table('found_children', function (Blueprint $table) {
                $table->string('district', 100)->nullable();
            });
        }
    }
};
