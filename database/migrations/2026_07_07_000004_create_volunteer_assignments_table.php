<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // tình nguyện viên
            $table->foreignId('clue_id')->constrained('clues')->cascadeOnDelete();

            $table->enum('status', ['ASSIGNED', 'VERIFIED', 'COULD_NOT_VERIFY'])->default('ASSIGNED');
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Không đặt UNIQUE cứng trên clue_id vì 1 manh mối có thể được re-assign sau khi
            // COULD_NOT_VERIFY. Việc "không cho 2 volunteer nhận trùng 1 clue đang ASSIGNED"
            // được enforce ở tầng ứng dụng (D-02: VolunteerAssignmentController), không phải DB.
            $table->index(['clue_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_assignments');
    }
};
