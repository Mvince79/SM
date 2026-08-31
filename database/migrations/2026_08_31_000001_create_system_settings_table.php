<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng system_settings — lưu các "override" do admin chỉnh trên giao diện,
 * đè lên giá trị mặc định trong config/notification.php & config/face_match.php.
 *
 * Mỗi dòng = 1 thông số (group, key). value lưu dạng JSON (kiểu do cột `type`
 * quyết định) để cùng 1 bảng chứa được cả số, chuỗi, mảng. Khi không có dòng
 * nào, hệ thống dùng giá trị mặc định trong file config (fallback an toàn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');                 // 'notification' | 'face_match'
            $table->string('key');                   // 'score_threshold' | 'reminder_milestones' | ...
            $table->text('value')->nullable();       // JSON-encoded theo `type`
            $table->string('type');                  // int | float | string | select | milestones
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
