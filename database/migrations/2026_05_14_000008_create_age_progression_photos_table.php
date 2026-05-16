<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('age_progression_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('missing_id');
            $table->unsignedBigInteger('original_photo_id')->nullable(); // FK → photos.id
            $table->string('image_path', 500);
            $table->tinyInteger('age_at_missing')->unsigned()->nullable();
            $table->tinyInteger('estimated_age')->unsigned();
            $table->tinyInteger('years_elapsed')->unsigned();
            $table->string('model_used', 100)->nullable();     // 'FRAN', 'HRFAE', 'Replicate/fran'
            $table->string('api_provider', 50)->nullable();    // 'replicate', 'local', 'opencv_fallback'
            $table->tinyInteger('is_approved')->unsigned()->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            // Indexes
            $table->index('missing_id');
            $table->index('is_approved');
            $table->index('generated_at');

            // Foreign Keys
            $table->foreign('missing_id')
                ->references('id')->on('missing_children')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('original_photo_id')
                ->references('id')->on('photos')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('approved_by')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_progression_photos');
    }
};
