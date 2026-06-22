<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->decimal('watch_lat', 10, 7)->nullable()->after('device_name');
            $table->decimal('watch_lng', 10, 7)->nullable()->after('watch_lat');
            $table->tinyInteger('watch_radius_km')->unsigned()->default(20)->after('watch_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropColumn(['watch_lat', 'watch_lng', 'watch_radius_km']);
        });
    }
};