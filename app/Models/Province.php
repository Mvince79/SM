<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'provinces';

    protected $fillable = [
        'name',
        'code',
        'short_code',
        'region',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'integer',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'province_id');
    }

    public function missingChildren()
    {
        return $this->hasMany(MissingChild::class, 'province_id');
    }
    public function handoverPoints()
    {
        return $this->hasMany(HandoverPoint::class, 'province_id');
    }

    /**
     * Chuẩn hóa tên đơn vị hành chính để so khớp bất chấp tiền tố/thể hiện:
     *   "Cần Thơ" ↔ "TP Cần Thơ"
     *   "TP. Hồ Chí Minh" ↔ "TP Hồ Chí Minh"
     *   "An Giang" ↔ "An Giang"
     * Bỏ tiền tố "TP." / "TP " / "Tỉnh " / "Thành phố ", lower-case, trim.
     */
    protected static function normName(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/^(tp\.?|tỉnh|thành phố)\s+/iu', '', $name)));
    }

    /**
     * Đối chiếu tên tỉnh/thành (vd. trả về từ reverse-geocode GeoJSON) sang bản
     * ghi Province trong DB, bất chấp khác biệt tiền tố. Dùng để tự động điền
     * dropdown province_id từ toạ độ định vị. Trả null nếu không khớp.
     */
    public static function resolveByName(string $name): ?self
    {
        $target = self::normName($name);

        if ($target === '') {
            return null;
        }

        static $map = null;

        if ($map === null) {
            $map = self::where('is_active', 1)
                ->pluck('name', 'id')
                ->mapWithKeys(fn ($dbName, $id) => [self::normName((string) $dbName) => $id])
                ->all();
        }

        if (array_key_exists($target, $map)) {
            return self::find($map[$target]);
        }

        return null;
    }
}
