<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('face_match_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('missing_id');
            $table->unsignedBigInteger('found_id');
            $table->unsignedBigInteger('missing_photo_id')->nullable(); // FK → photos.id (ảnh missing dùng)
            $table->unsignedBigInteger('found_photo_id')->nullable();   // FK → photos.id (ảnh found dùng)
            $table->decimal('score', 5, 4);                             // DECIMAL(5,4): 0.0000–1.0000
            $table->tinyInteger('is_matched')->unsigned()->default(0);  // score >= 0.70 → true
            $table->enum('source', ['REALTIME', 'AGE_PROGRESSION', 'MANUAL'])->default('REALTIME');
            $table->string('model_used', 50)->nullable();               // 'ArcFace', 'FaceNet', 'DeepFace'
            $table->enum('status', ['PENDING', 'CONFIRMED', 'DISMISSED'])->default('PENDING');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // UNIQUE constraint cấp ảnh, không phải cấp hồ sơ
            // Cho phép nhiều cặp ảnh khác nhau từ cùng hồ sơ
            $table->unique(['missing_photo_id', 'found_photo_id']);

            // Indexes
            $table->index('missing_id');
            $table->index('found_id');
            $table->index('score');
            $table->index('is_matched');
            $table->index('status');

            // Foreign Keys
            $table->foreign('missing_id')
                ->references('id')->on('missing_children')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('found_id')
                ->references('id')->on('found_children')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('missing_photo_id')
                ->references('id')->on('photos')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('found_photo_id')
                ->references('id')->on('photos')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('confirmed_by')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_match_results');
    }
};
