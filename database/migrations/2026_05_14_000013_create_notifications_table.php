<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();              // CHAR(36) UUID — PK
            $table->string('type');                     // Class notification đầy đủ
            $table->string('notifiable_type');          // 'App\Models\User'
            $table->unsignedBigInteger('notifiable_id');
            $table->json('data');                       // Payload thông báo
            $table->timestamp('read_at')->nullable();   // NULL = chưa đọc
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
