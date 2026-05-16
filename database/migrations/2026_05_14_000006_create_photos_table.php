<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100);          // 'App\Models\MissingChild' hoặc 'App\Models\FoundChild'
            $table->unsignedBigInteger('model_id');      // ID của record tương ứng
            $table->string('path_original', 500);        // Ảnh gốc full resolution — auth only
            $table->string('path_thumb', 500)->nullable();    // Thumbnail 200×200 (public)
            $table->string('path_blurred', 500)->nullable();  // Ảnh làm mờ (public, chưa đăng nhập)
            $table->unsignedInteger('file_size')->nullable(); // Bytes
            $table->string('mime_type', 50)->nullable();      // image/jpeg, image/png, image/webp
            $table->smallInteger('width')->unsigned()->nullable();   // Chiều rộng ảnh gốc (px)
            $table->smallInteger('height')->unsigned()->nullable();  // Chiều cao ảnh gốc (px)
            $table->tinyInteger('has_face')->unsigned()->default(0); // AI detect: có khuôn mặt không
            $table->text('face_encoding')->nullable();               // JSON face vector — ENCRYPTED AES-256
            $table->tinyInteger('is_primary')->unsigned()->default(0);
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            // Polymorphic index (model_type + model_id)
            $table->index(['model_type', 'model_id']);
            $table->index('has_face');
            $table->index('is_primary');

            // Không có DB-level FK cho polymorphic — cascade xử lý bằng Laravel Observer
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
