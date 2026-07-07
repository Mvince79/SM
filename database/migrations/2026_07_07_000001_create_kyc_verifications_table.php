<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // id_number LUÔN được mã hóa AES-256 qua Crypt::encryptString() trước khi lưu
            // (xem mutator setIdNumberAttribute trong Model) — giống nguyên tắc đã áp dụng
            // cho photos.face_encoding ở Sprint 3, KHÔNG BAO GIỜ lưu plain text.
            $table->text('id_number');

            $table->string('full_name_on_id')->nullable();
            $table->date('dob_on_id')->nullable();

            $table->string('front_image_path');
            $table->string('back_image_path');
            $table->string('selfie_path');

            $table->string('ocr_provider')->nullable(); // 'fpt_ai' | 'vnpt_ekyc' | 'manual'
            $table->json('ocr_raw_response')->nullable();
            $table->decimal('face_match_score', 5, 4)->nullable(); // CCCD selfie vs ảnh mặt trước

            $table->enum('status', ['PENDING', 'VERIFIED', 'REJECTED'])->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejected_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
