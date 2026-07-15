<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')
                ->constrained('notification_events')->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->string('role_at_time');
            $table->string('rendered_title');
            $table->text('rendered_body');
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Phục vụ Trung tâm thông báo (J-07) truy vấn nhanh
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
