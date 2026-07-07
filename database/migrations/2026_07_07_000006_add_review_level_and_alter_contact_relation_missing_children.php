<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->enum('review_level', ['STANDARD', 'ENHANCED'])
                ->default('STANDARD')
                ->after('status');
        });


        DB::table('missing_children')->update([
            'contact_relation' => DB::raw("
                CASE
                    WHEN contact_relation IN ('Mẹ', 'Cha', 'Bố') THEN 'PARENT'
                    WHEN contact_relation = 'Ông/Bà' THEN 'GRANDPARENT'
                    WHEN contact_relation = 'Người giám hộ hợp pháp' THEN 'LEGAL_GUARDIAN'
                    WHEN contact_relation = 'Người phát hiện trẻ đi lạc' THEN 'WITNESS_NO_RELATION'
                    WHEN contact_relation IN ('PARENT','GRANDPARENT','LEGAL_GUARDIAN','SIBLING','RELATIVE_OTHER','WITNESS_NO_RELATION','ORGANIZATION') THEN contact_relation
                    ELSE 'RELATIVE_OTHER'
                END
            "),
        ]);


        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_children MODIFY contact_relation ENUM('PARENT','GRANDPARENT','LEGAL_GUARDIAN','SIBLING','RELATIVE_OTHER','WITNESS_NO_RELATION','ORGANIZATION') NOT NULL DEFAULT 'PARENT'");
        } else {
            Schema::table('missing_children', function (Blueprint $table) {
                $table->string('contact_relation', 30)->default('PARENT')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_children MODIFY contact_relation VARCHAR(255) NOT NULL DEFAULT 'Mẹ'");
        } else {
            Schema::table('missing_children', function (Blueprint $table) {
                $table->string('contact_relation', 255)->default('Mẹ')->change();
            });
        }

        Schema::table('missing_children', function (Blueprint $table) {
            $table->dropColumn('review_level');
        });
    }
};
