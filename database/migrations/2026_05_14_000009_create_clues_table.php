<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('missing_id');
            $table->unsignedBigInteger('user_id')->nullable(); // NULL = ẩn danh
            $table->enum('clue_type', ['SIGHTING', 'RUMOR', 'PHOTO', 'VIDEO', 'OTHER'])->default('SIGHTING');
            $table->text('description');
            $table->decimal('lat', 10, 7)->nullable();    // Vĩ độ GPS (Geolocation API)
            $table->decimal('lng', 10, 7)->nullable();    // Kinh độ GPS
            $table->timestamp('gps_shared_at')->nullable();             // Thời điểm user bấm "Chia sẻ vị trí" — NULL = không share GPS
            $table->smallInteger('location_accuracy_m')->unsigned()->nullable(); // SMALLINT UNSIGNED: độ chính xác GPS (mét)
            $table->dateTime('occurred_at')->nullable();  // Thời điểm xảy ra (nếu biết)
            $table->string('image_path', 500)->nullable();
            $table->tinyInteger('is_verified')->unsigned()->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();      // FK → users.id (admin xác minh)
            $table->tinyInteger('is_anonymous')->unsigned()->default(0);
            $table->timestamps();

            // Indexes
            $table->index('missing_id');
            $table->index('user_id');
            $table->index('clue_type');
            $table->index('is_verified');
            $table->index(['lat', 'lng']);

            // 3 Foreign Keys (bắt buộc đủ cả 3 — fix v2.2)
            $table->foreign('missing_id')
                ->references('id')->on('missing_children')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('verified_by')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clues');
    }
};
