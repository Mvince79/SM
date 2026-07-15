<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('third_party_verifier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()
                ->constrained('users')->cascadeOnDelete();
            $table->enum('organization_type', ['POLICE', 'GOVERNMENT', 'NGO']);
            $table->string('organization_name');
            $table->string('badge_or_staff_id')->nullable();
            $table->foreignId('handover_point_id')->nullable()
                ->constrained('handover_points')->nullOnDelete();
            $table->foreignId('verified_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('third_party_verifier_profiles');
    }
};
