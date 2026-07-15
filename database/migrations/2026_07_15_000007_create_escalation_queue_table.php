<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_recipient_id')->unique()
                ->constrained('notification_recipients')->cascadeOnDelete();
            $table->timestamp('due_at');
            $table->enum('status', ['PENDING', 'ESCALATED', 'RESOLVED'])
                ->default('PENDING');
            $table->foreignId('resolved_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_queue');
    }
};
