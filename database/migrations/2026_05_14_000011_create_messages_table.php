<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->unsignedBigInteger('missing_id')->nullable(); // Ngữ cảnh hồ sơ
            $table->text('body')->nullable();                      // Nội dung văn bản
            $table->string('attachment_path', 500)->nullable();    // File đính kèm
            $table->enum('attachment_type', ['IMAGE', 'VIDEO', 'FILE'])->nullable();
            $table->timestamp('read_at')->nullable();              // NULL = chưa đọc
            $table->timestamps();

            // Indexes
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('missing_id');
            $table->index('read_at');
            $table->index(['from_user_id', 'to_user_id', 'missing_id']);

            // Foreign Keys
            $table->foreign('from_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('to_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('missing_id')
                ->references('id')->on('missing_children')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // CHECK constraint DB-level (MySQL 8.0+): không cho phép insert tin nhắn rỗng
        // fix v2.2: Enforce ở cả DB lẫn Laravel, tránh insert rỗng từ bất kỳ nguồn nào
        DB::statement('ALTER TABLE messages ADD CONSTRAINT chk_messages_content CHECK (body IS NOT NULL OR attachment_path IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
