<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('handover_points', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'POLICE_STATION',
                'COMMUNE_PEOPLE_COMMITTEE',
                'HOTLINE_111',
                'NGO',
                'OTHER',
            ]);
            $table->string('name');
            $table->string('address');

            // provinces.id là TINYINT UNSIGNED (chỉ 34 tỉnh) — PHẢI khớp kiểu,
            // không dùng foreignId() vì mặc định tạo BIGINT UNSIGNED (gây lỗi 3780)
            $table->unsignedTinyInteger('province_id');
            $table->foreign('province_id')
                ->references('id')->on('provinces')
                ->cascadeOnDelete();

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('hotline_phone')->nullable();
            $table->string('responsible_org_name');
            $table->text('legal_authority_note')->nullable();
            $table->string('verification_document_path', 500)->nullable();
            $table->integer('priority_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Phục vụ Service H-04 (HandoverPointFinderService) tra cứu nhanh theo tỉnh
            $table->index(['province_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_points');
    }
};