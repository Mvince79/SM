<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->morphs('subject'); // subject_type + subject_id
            $table->json('payload')->nullable();
            $table->foreignId('triggered_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Phục vụ truy vấn dashboard và escalation
            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
