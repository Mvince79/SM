<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();      // Token ẩn danh cho người chưa đăng nhập
            $table->unsignedBigInteger('user_id')->nullable();  // NULL nếu chưa login
            $table->enum('intent', ['REPORT_MISSING', 'REPORT_FOUND', 'SEARCH', 'GENERAL'])->nullable();
            $table->string('current_step', 50)->nullable();
            $table->json('collected_data')->nullable();         // Schema: {name, age, gender, location, missing_date...}
            $table->enum('status', ['ACTIVE', 'COMPLETED', 'ABANDONED'])->default('ACTIVE');
            $table->string('completed_action', 100)->nullable(); // 'redirected_to_post_form', 'submitted_report'...
            $table->string('ip_address', 45)->nullable();        // IPv4/IPv6
            $table->string('user_agent', 300)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');

            // Foreign Key
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_sessions');
    }
};
