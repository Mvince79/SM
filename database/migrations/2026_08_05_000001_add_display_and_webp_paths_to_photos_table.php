<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('path_display')->nullable()->after('path_thumb');
            $table->string('path_thumb_webp')->nullable()->after('path_thumb');
            $table->string('path_display_webp')->nullable()->after('path_display');
            $table->unsignedInteger('file_size_display')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn([
                'path_display',
                'path_thumb_webp',
                'path_display_webp',
                'file_size_display',
            ]);
        });
    }
};
