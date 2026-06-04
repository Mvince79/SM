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
        Schema::table('clues', function (Blueprint $table) {
            $table->tinyInteger('is_hidden')->unsigned()->default(0)
                ->after('is_anonymous');
            $table->timestamp('verified_at')->nullable()
                ->after('is_hidden');
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('clues', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn(['is_hidden', 'verified_at']);
        });
    }
};
