<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->enum('id_type', ['CMND_9', 'CCCD_12'])->nullable()->after('id');
        });
        // Validate định dạng số giấy tờ (^\d{9}$|^\d{12}$) thực hiện ở
        // Request layer (K-02, Thanh), không ràng buộc ở DB.
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn('id_type');
        });
    }
};
