<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();

            // UNIQUE là phần quan trọng nhất của cả bảng này — đây là thứ chặn race condition
            // ở tầng DB (MySQL) ngay cả khi 2 request đến gần như đồng thời (E-01a/b/c/d).
            $table->uuid('request_key')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resource_type', 50); // 'missing_children' | 'found_children' | 'clues'
            $table->unsignedBigInteger('resource_id')->nullable(); // id record đã tạo, để trả lại nếu gửi lại

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
