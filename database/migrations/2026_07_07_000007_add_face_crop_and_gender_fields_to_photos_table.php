<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Dùng cho B-01: lưu bounding box trả về từ RetinaFace/MTCNN (x, y, w, h)
            $table->json('face_bounding_box')->nullable()->after('face_encoding');

            // Dùng cho B-01: ảnh đã crop+align chỉ vùng mặt, dùng để demo "before/after crop"
            // cho hội đồng xem trực quan pipeline, và encoding (Sprint 3) sẽ tính từ ảnh này
            // thay vì path_original.
            $table->string('face_crop_path', 500)->nullable()->after('face_bounding_box');

            // Dùng cho B-02: gợi ý giới tính từ DeepFace.analyze() — chỉ là GỢI Ý AI,
            // không ghi đè gender/gender_estimate do người dùng nhập tay.
            $table->enum('detected_gender', ['MALE', 'FEMALE', 'UNKNOWN'])->nullable()->after('face_crop_path');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['face_bounding_box', 'face_crop_path', 'detected_gender']);
        });
    }
};
