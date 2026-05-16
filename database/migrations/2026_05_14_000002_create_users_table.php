<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                       // BIGINT UNSIGNED AUTO_INCREMENT
            $table->string('full_name', 100);
            $table->string('email', 191)->unique();             // 191 vì utf8mb4 index limit 767 bytes
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 15)->nullable();            // E.164: +84912345678
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password', 255)->nullable();        // NULL nếu đăng nhập Google OAuth
            $table->string('google_id', 100)->nullable()->unique();
            $table->string('avatar_url', 500)->nullable();
            $table->enum('role', ['ADMIN', 'MODERATOR', 'USER'])->default('USER');
            $table->enum('status', ['ACTIVE', 'BANNED', 'PENDING'])->default('ACTIVE');
            $table->text('ban_reason')->nullable();
            $table->tinyInteger('province_id')->unsigned()->nullable();
            $table->decimal('notify_lat', 10, 7)->nullable();
            $table->decimal('notify_lng', 10, 7)->nullable();
            $table->tinyInteger('notify_radius_km')->unsigned()->default(50); // 10/30/50/100
            $table->string('otp_code', 255)->nullable();        // VARCHAR(255): bcrypt hash ~60 ký tự
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();                              // deleted_at: soft delete (GDPR Art.17)

            // Indexes
            $table->index('role');
            $table->index('status');
            $table->index('province_id');
            $table->index(['notify_lat', 'notify_lng']);
            $table->index('deleted_at');

            // Foreign Key
            $table->foreign('province_id')
                ->references('id')->on('provinces')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
