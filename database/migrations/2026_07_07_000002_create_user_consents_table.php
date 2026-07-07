<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('consent_type', ['TOS', 'PRIVACY_POLICY', 'CHILD_DATA_POLICY', 'KYC_CONSENT']);
            $table->string('version', 20); // vd: 'v1.0' — tăng khi nội dung chính sách đổi (A-05)
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
