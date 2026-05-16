<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('found_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('province_id')->unsigned();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('address_detail', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->date('found_date');
            $table->time('found_time')->nullable();
            $table->enum('gender_estimate', ['MALE', 'FEMALE', 'UNKNOWN'])->nullable();
            $table->tinyInteger('age_estimate')->unsigned()->nullable();
            $table->text('condition_desc')->nullable();
            $table->text('clothes_desc')->nullable();
            $table->text('special_marks')->nullable();
            $table->string('current_location', 300)->nullable();  // đồn PA, BV, mái ấm...
            $table->string('handover_agency', 200)->nullable();    // CA phường, BV đã bàn giao
            $table->enum('status', ['PENDING', 'APPROVED', 'MATCHED', 'CLOSED'])->default('PENDING');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('ai_processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('province_id');
            $table->index('status');
            $table->index('found_date');
            $table->index(['lat', 'lng']);

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

        // FULLTEXT index
        DB::statement('ALTER TABLE found_children ADD FULLTEXT INDEX ft_found_search (condition_desc, clothes_desc, special_marks)');
    }

    public function down(): void
    {
        Schema::dropIfExists('found_children');
    }
};
