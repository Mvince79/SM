<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->tinyInteger('id', true, true); // TINYINT UNSIGNED AUTO_INCREMENT, 34 tỉnh < 255
            $table->string('name', 100);
            $table->char('code', 6)->unique();      // CHAR(6): VN-DNA, VN-TAY, VN-HUE dài 6 ký tự
            $table->char('short_code', 3)->unique(); // CHAR(3): mã nội bộ VD: HAN, HCM
            $table->enum('region', ['BAC', 'TRUNG', 'NAM']);
            $table->tinyInteger('is_active')->unsigned()->default(1); // TINYINT(1) dự phòng sáp nhập
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
