<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_chat_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['ACTIVE', 'CLOSED'])->default('ACTIVE');
            $table->timestamp('assigned_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Phục vụ query đếm COUNT(status=ACTIVE) GROUP BY admin_user_id (round-robin theo tải)
            $table->index(['admin_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_chat_assignments');
    }
};
