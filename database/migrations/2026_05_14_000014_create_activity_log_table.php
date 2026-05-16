<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->default('default'); // 'admin', 'user', 'ai'
            $table->text('description');
            $table->nullableMorphs('subject');              // subject_type VARCHAR(255), subject_id BIGINT UNSIGNED
            $table->string('event')->nullable();            // 'created', 'updated', 'deleted'
            $table->nullableMorphs('causer');               // causer_type VARCHAR(255), causer_id BIGINT UNSIGNED
            $table->json('properties')->nullable();         // Dữ liệu trước/sau thay đổi (old, attributes)
            $table->uuid('batch_uuid')->nullable();         // CHAR(36): nhóm actions liên quan
            $table->timestamps();

            $table->index('log_name');
            $table->index('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
