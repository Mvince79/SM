<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('ADMIN','MODERATOR','VOLUNTEER','USER') NOT NULL DEFAULT 'USER'");
        } else {

            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('USER')->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('kyc_status', ['NONE', 'PENDING', 'VERIFIED', 'REJECTED'])
                ->default('NONE')
                ->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kyc_status');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('ADMIN','MODERATOR','USER') NOT NULL DEFAULT 'USER'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('USER')->change();
            });
        }
    }
};
