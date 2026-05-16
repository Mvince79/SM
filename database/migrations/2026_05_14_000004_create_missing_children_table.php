<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('missing_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('case_code', 20)->unique(); // MC-2026-00001 (auto-gen)
            $table->string('full_name', 100);
            $table->date('dob')->nullable();
            $table->tinyInteger('age_estimate')->unsigned()->nullable();
            $table->enum('gender', ['MALE', 'FEMALE', 'UNKNOWN']);
            $table->enum('skin_tone', ['LIGHT', 'MEDIUM', 'DARK'])->nullable();
            $table->smallInteger('height_cm')->unsigned()->nullable();    // SMALLINT UNSIGNED
            $table->decimal('weight_kg', 5, 2)->nullable();               // DECIMAL(5,2)
            $table->tinyInteger('province_id')->unsigned();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('address_detail', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->date('missing_date');
            $table->time('missing_time')->nullable();
            $table->text('clothes_desc')->nullable();
            $table->string('clothes_color', 100)->nullable();
            $table->text('special_marks')->nullable();
            $table->text('circumstances')->nullable();
            $table->string('reward_info', 300)->nullable();
            $table->string('contact_name', 100);
            $table->string('contact_phone', 15);                          // KHÔNG trả về API public
            $table->string('contact_relation', 50)->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'FOUND', 'CLOSED', 'REJECTED'])->default('PENDING');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('found_at')->nullable();
            $table->timestamp('auto_expire_notified_at')->nullable();
            $table->string('qr_code_path', 500)->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('province_id');
            $table->index('status');
            $table->index('gender');
            $table->index('missing_date');
            $table->index(['lat', 'lng']);
            $table->index('deleted_at');

            // Foreign Keys
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('province_id')
                ->references('id')->on('provinces')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('approved_by')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // FULLTEXT index — phải dùng DB::statement vì Blueprint không hỗ trợ FULLTEXT trực tiếp
        DB::statement('ALTER TABLE missing_children ADD FULLTEXT INDEX ft_missing_search (full_name, clothes_desc, special_marks, circumstances)');
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_children');
    }
};
