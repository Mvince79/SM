<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('commentable_type', 100);    // 'App\Models\MissingChild' hoặc 'App\Models\FoundChild'
            $table->unsignedBigInteger('commentable_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable(); // Self-ref: reply — 1 cấp
            $table->text('body');
            $table->tinyInteger('is_approved')->unsigned()->default(1);  // Mặc định duyệt ngay
            $table->tinyInteger('is_hidden')->unsigned()->default(0);    // Admin ẩn (spam/vi phạm)
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['commentable_type', 'commentable_id']);
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('is_approved');

            // Foreign Keys
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Self-referencing FK
            $table->foreign('parent_id')
                ->references('id')->on('comments')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Không có DB-level FK cho commentable_type/commentable_id (polymorphic)
            // Xử lý bằng Laravel Observer
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
