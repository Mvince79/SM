<?php

namespace Database\Seeders;

use App\Models\FoundChild;
use App\Models\User;
use Database\Seeders\Concerns\GeneratesVietnameseDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * S6-A01 — Seed dữ liệu Hồ sơ Trẻ tìm thấy (demo).
 *
 * Sinh ~15 hồ sơ `found_children` trải đều 5 tỉnh demo và các trạng thái
 * PENDING / APPROVED / MATCHED / CLOSED, kèm ảnh minh họa để trang danh
 * sách/bản đồ không bị trống hoặc vỡ ảnh khi demo.
 *
 * Chạy độc lập: php artisan db:seed --class=DemoFoundChildrenSeeder
 */
class DemoFoundChildrenSeeder extends Seeder
{
    use GeneratesVietnameseDemoData;

    private const TOTAL_RECORDS = 15;

    private const AVATAR_DIR = 'demo-photos';

    public function run(): void
    {
        $reporters = $this->ensureDemoReporters();
        $provinces = $this->demoProvinces();
        $provinceIds = array_keys($provinces);
        $statuses = ['PENDING', 'APPROVED', 'MATCHED', 'CLOSED'];

        $now = now();

        for ($i = 1; $i <= self::TOTAL_RECORDS; $i++) {
            $provinceId = $provinceIds[$i % count($provinceIds)];
            $area = $provinces[$provinceId]['areas'][array_rand($provinces[$provinceId]['areas'])];
            $status = $statuses[$i % count($statuses)];
            $gender = ['MALE', 'FEMALE', 'UNKNOWN'][array_rand(['MALE', 'FEMALE', 'UNKNOWN'])];

            $foundAt = $now->copy()->subDays(random_int(0, 60))->subHours(random_int(0, 23));
            $reporter = $reporters[array_rand($reporters)];

            $approvedAt = in_array($status, ['APPROVED', 'MATCHED', 'CLOSED'], true)
                ? $foundAt->copy()->addHours(random_int(1, 10))
                : null;

            $found = FoundChild::create([
                'user_id' => $reporter->id,
                'province_id' => $provinceId,
                'district' => $area['district'],
                'ward' => $area['ward'],
                'address_detail' => 'Gần ' . $area['ward'] . ', ' . $area['district'],
                'lat' => $area['lat'] + (random_int(-30, 30) / 10000),
                'lng' => $area['lng'] + (random_int(-30, 30) / 10000),
                'found_date' => $foundAt->format('Y-m-d'),
                'found_time' => $foundAt->format('H:i:s'),
                'gender_estimate' => $gender,
                'age_estimate' => random_int(2, 14),
                'condition_desc' => $this->conditionDescPool()[array_rand($this->conditionDescPool())],
                'clothes_desc' => $this->clothesDescPool()[array_rand($this->clothesDescPool())],
                'special_marks' => $this->specialMarksPool()[array_rand($this->specialMarksPool())],
                'current_location' => 'Trung tâm Bảo trợ Xã hội ' . $provinces[$provinceId]['name'],
                'handover_agency' => 'Công an ' . $area['district'],
                'status' => $status,
                'approved_at' => $approvedAt,
                'approved_by' => null,
                'created_at' => $foundAt,
                'updated_at' => $approvedAt ?? $foundAt,
            ]);

            $avatarPrefix = $gender === 'FEMALE' ? 'f' : ($gender === 'MALE' ? 'm' : ($i % 2 === 0 ? 'f' : 'm'));
            $mood = $status === 'CLOSED' || $status === 'MATCHED' ? 'happy' : 'neutral';
            $variant = str_pad((string) (($i % 4) + 1), 2, '0', STR_PAD_LEFT);
            $avatarBase = "avatar_{$avatarPrefix}_{$mood}_{$variant}";

            DB::table('photos')->insert([
                'model_type' => 'App\\Models\\FoundChild',
                'model_id' => $found->id,
                'path_original' => self::AVATAR_DIR . "/{$avatarBase}_original.jpg",
                'path_thumb' => self::AVATAR_DIR . "/{$avatarBase}_thumb.jpg",
                'path_thumb_webp' => self::AVATAR_DIR . "/{$avatarBase}_thumb.webp",
                'path_blurred' => self::AVATAR_DIR . "/{$avatarBase}_blurred.jpg",
                'path_display' => self::AVATAR_DIR . "/{$avatarBase}_display.jpg",
                'path_display_webp' => self::AVATAR_DIR . "/{$avatarBase}_display.webp",
                'file_size' => random_int(18000, 26000),
                'file_size_display' => random_int(12000, 16000),
                'mime_type' => 'image/jpeg',
                'width' => 800,
                'height' => 800,
                'has_face' => 1,
                'face_encoding' => null,
                'ai_status' => 'done',
                'is_primary' => 1,
                'sort_order' => 0,
                'created_at' => $foundAt,
                'updated_at' => $foundAt,
            ]);
        }

        $this->command?->info('Đã seed ' . self::TOTAL_RECORDS . ' hồ sơ trẻ tìm thấy demo (S6-A01).');
    }

    /**
     * @return array<int, User>
     */
    private function ensureDemoReporters(): array
    {
        $emails = [
            'demo.reporter1@timtre.vn',
            'demo.reporter2@timtre.vn',
            'demo.reporter3@timtre.vn',
        ];

        $reporters = [];

        foreach ($emails as $email) {
            $reporters[] = User::firstOrCreate(
                ['email' => $email],
                [
                    'full_name' => $this->randomFullName('FEMALE'),
                    'phone' => $this->randomPhone(),
                    'password' => Hash::make('Demo@123456'),
                    'role' => 'USER',
                    'status' => 'ACTIVE',
                    'kyc_status' => 'NONE',
                    'email_verified_at' => now(),
                ]
            );
        }

        return $reporters;
    }
}
