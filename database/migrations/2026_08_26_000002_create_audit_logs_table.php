<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D-01 — Bảng audit_logs ghi nhận mọi thay đổi trạng thái hồ sơ (bất biến).
     *
     * Không có cột updated_at, không có route/controller cho phép sửa/xóa
     * (chỉ ghi tự động qua Observer).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_role', ['ADMIN', 'SYSTEM', 'FAMILY'])->nullable();
            $table->string('action');              // ví dụ: missing_children.status_updated
            $table->string('target_type');         // ví dụ: missing_children
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id'], 'audit_logs_target_index');
            $table->index('actor_role', 'audit_logs_actor_role_index');
            $table->index('created_at', 'audit_logs_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
