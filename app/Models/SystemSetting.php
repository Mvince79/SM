<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bảng system_settings — 1 dòng = 1 thông số được admin ghi đè qua giao diện.
 *
 * Không có route/controller sửa trực tiếp; chỉ được đọc/ghi qua SettingService.
 * value lưu JSON, giải mã theo cột `type`.
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public $timestamps = true;

    /**
     * Giải mã value JSON về đúng kiểu theo `type`.
     *
     * @return mixed
     */
    public function decodedValue()
    {
        return json_decode((string) $this->value, true);
    }
}
