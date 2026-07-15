<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('category', ['BUG', 'COMPLAINT', 'FEEDBACK', 'FRAUD_SUSPECT']);
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])
                ->default('OPEN');
            $table->foreignId('assigned_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal_note')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
