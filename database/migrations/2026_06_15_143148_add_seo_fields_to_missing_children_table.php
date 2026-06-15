<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            $table->string('slug', 200)
                ->nullable()
                ->unique()
                ->after('case_code');

            $table->string('meta_title', 160)
                ->nullable()
                ->after('slug');

            $table->text('meta_description')
                ->nullable()
                ->after('meta_title');

            $table->string('og_image_url', 500)
                ->nullable()
                ->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('missing_children', function (Blueprint $table) {
            if (Schema::hasColumn('missing_children', 'slug')) {
                $table->dropColumn('slug');
            }

            if (Schema::hasColumn('missing_children', 'meta_title')) {
                $table->dropColumn('meta_title');
            }

            if (Schema::hasColumn('missing_children', 'meta_description')) {
                $table->dropColumn('meta_description');
            }

            if (Schema::hasColumn('missing_children', 'og_image_url')) {
                $table->dropColumn('og_image_url');
            }
        });
    }
};
