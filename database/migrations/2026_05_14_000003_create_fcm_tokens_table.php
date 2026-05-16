<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();                                           // BIGINT UNSIGNED
            $table->unsignedBigInteger('user_id');
            $table->string('token', 500);                           // FCM device token
            $table->enum('device_type', ['WEB', 'ANDROID', 'IOS'])->default('WEB');
            $table->string('device_name', 100)->nullable();         // VD: 'Chrome on Windows'
            $table->tinyInteger('is_active')->unsigned()->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique('token');
            $table->index('user_id');
            $table->index('is_active');

            // Foreign Key
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
