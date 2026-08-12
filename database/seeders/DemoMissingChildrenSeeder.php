<?php

namespace Database\Seeders;

use App\Models\MissingChild;
use App\Models\User;
use Database\Seeders\Concerns\GeneratesVietnameseDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * S6-A01 — Seed dữ liệu Hồ sơ Trẻ mất tích (demo).
 *
 * Sinh ~50 hồ sơ `missing_children` trải đều trạng thái PENDING / APPROVED /
 * FOUND (resolved) / REJECTED / CLOSED, trải đều 5 tỉnh demo (khớp O-01 —
 * HandoverPointsSeeder), đa dạng độ tuổi/giới tính/thời điểm mất tích, kèm
 * ảnh minh họa (không dùng ảnh trẻ em thật) và review_level theo
 * contact_relation (đúng luồng phân tầng rủi ro Sprint 3.5).
 *
 * Chạy độc lập: php artisan db:seed --class=DemoMissingChildrenSeeder
 * Không đụng tới ProvincesSeeder / UsersSeeder / user hệ thống đã có —
 * seeder này tự tạo bộ "người báo tin" demo riêng (firstOrCreate theo email).
 */
class DemoMissingChildrenSeeder extends Seeder
{
    use GeneratesVietnameseDemoData;

    /** Tổng số hồ sơ demo sẽ sinh ra. */
    private const TOTAL_RECORDS = 50;

    /** Số hồ sơ tối thiểu ở trạng thái FOUND (resolved) — DoD yêu cầu >= 5. */
    private const RESOLVED_COUNT = 6;

    private const AVATAR_DIR = 'demo-photos';

    public function run(): void
    {
        $reporters = $this->ensureDemoReporters();
        $moderator = $this->ensureModerator();
        $provinces = $this->demoProvinces();
        $provinceIds = array_keys($provinces);

        $statusPlan = $this->buildStatusPlan();

        $avatarsMale = $this->avatarSet('m');
        $avatarsFemale = $this->avatarSet('f');

        $now = now();
        $seq = 1;

        foreach ($statusPlan as $status) {
            $gender = random_int(0, 1) === 0 ? 'MALE' : 'FEMALE';
            $provinceId = $provinceIds[$seq % count($provinceIds)];
            $area = $provinces[$provinceId]['areas'][array_rand($provinces[$provinceId]['areas'])];

            $isRecentUrgent = $status === 'PENDING' && $seq % 4 === 0; // vài case "khẩn cấp" đăng trong ngày
            $ageYears = random_int(2, 15);
            $missingDaysAgo = $isRecentUrgent
                ? random_int(0, 1)
                : ($status === 'CLOSED' ? random_int(120, 220) : random_int(1, 90));

            $missingAt = $now->copy()->subDays($missingDaysAgo)->subHours(random_int(0, 23));
            $createdAt = $missingAt->copy()->addHours(random_int(1, 6)); // báo tin sau khi phát hiện mất tích

            $contactRelation = $this->pickContactRelation();
            $reviewLevel = in_array($contactRelation, ['PARENT', 'LEGAL_GUARDIAN'], true)
                ? 'STANDARD'
                : 'ENHANCED';

            $reporter = $reporters[array_rand($reporters)];
            $fullName = $this->randomFullName($gender);
            $caseCode = sprintf('MC-DEMO-%05d', $seq);
            $slug = Str::slug($fullName) . '-' . strtolower($caseCode);

            [$approvedAt, $approvedBy, $foundAt, $rejectionReason] = $this->statusTimestamps(
                $status,
                $createdAt,
                $moderator->id,
            );

            $child = MissingChild::create([
                'user_id' => $reporter->id,
                'case_code' => $caseCode,
                'slug' => $slug,
                'full_name' => $fullName,
                'dob' => $now->copy()->subYears($ageYears)->subDays(random_int(0, 364))->format('Y-m-d'),
                'age_estimate' => null,
                'gender' => $gender,
                'skin_tone' => ['LIGHT', 'MEDIUM', 'DARK'][array_rand(['LIGHT', 'MEDIUM', 'DARK'])],
                'height_cm' => random_int(80, 165),
                'weight_kg' => round(random_int(120, 550) / 10, 1),
                'province_id' => $provinceId,
                'district' => $area['district'],
                'ward' => $area['ward'],
                'address_detail' => 'Gần ' . $area['ward'] . ', ' . $area['district'],
                'lat' => $area['lat'] + (random_int(-30, 30) / 10000),
                'lng' => $area['lng'] + (random_int(-30, 30) / 10000),
                'missing_date' => $missingAt->format('Y-m-d'),
                'missing_time' => $missingAt->format('H:i:s'),
                'clothes_desc' => $this->clothesDescPool()[array_rand($this->clothesDescPool())],
                'clothes_color' => null,
                'special_marks' => $this->specialMarksPool()[array_rand($this->specialMarksPool())],
                'circumstances' => $this->circumstancesPool()[array_rand($this->circumstancesPool())],
                'reward_info' => random_int(0, 4) === 0 ? 'Gia đình xin hậu tạ người cung cấp thông tin chính xác.' : null,
                'contact_name' => $this->randomAdultName($gender === 'MALE' ? 'FEMALE' : 'MALE'),
                'contact_phone' => $this->randomPhone(),
                'contact_relation' => $contactRelation,
                'status' => $status,
                'review_level' => $reviewLevel,
                'approved_at' => $approvedAt,
                'approved_by' => $approvedBy,
                'rejection_reason' => $rejectionReason,
                'found_at' => $foundAt,
                'expires_at' => $createdAt->copy()->addYears(2),
                'qr_code_path' => in_array($status, ['APPROVED', 'FOUND', 'CLOSED'], true)
                    ? "qr_codes/{$caseCode}.svg"
                    : null,
                'view_count' => in_array($status, ['PENDING', 'REJECTED'], true) ? 0 : random_int(5, 3500),
                'share_count' => in_array($status, ['PENDING', 'REJECTED'], true) ? 0 : random_int(0, 500),
                'created_at' => $createdAt,
                'updated_at' => $foundAt ?? $approvedAt ?? $createdAt,
            ]);

            $avatarPool = $gender === 'MALE' ? $avatarsMale : $avatarsFemale;

            // Ảnh "trước" (lúc báo mất tích) — luôn có, tránh vỡ ảnh trên mọi trang danh sách/bản đồ.
            $this->attachPhoto($child->id, $avatarPool['neutral'][array_rand($avatarPool['neutral'])], $createdAt, isPrimary: true, sortOrder: 0);

            // Case FOUND (resolved): thêm ảnh "sau" chụp gần thời điểm tìm thấy, dùng cho trang /missing/{id}/found.
            if ($status === 'FOUND' && $foundAt) {
                $this->attachPhoto($child->id, $avatarPool['happy'][array_rand($avatarPool['happy'])], $foundAt, isPrimary: false, sortOrder: 1);
            }

            $seq++;
        }

        $this->command?->info('Đã seed ' . self::TOTAL_RECORDS . ' hồ sơ trẻ mất tích demo (S6-A01).');
    }

    /**
     * @return array<int, string> Danh sách status cho từng bản ghi, đã trải đều.
     */
    private function buildStatusPlan(): array
    {
        $plan = array_merge(
            array_fill(0, self::RESOLVED_COUNT, 'FOUND'),
            array_fill(0, 26, 'APPROVED'),
            array_fill(0, 10, 'PENDING'),
            array_fill(0, 4, 'REJECTED'),
            array_fill(0, 4, 'CLOSED'),
        );

        // Bù/cắt cho đúng TOTAL_RECORDS nếu hằng số bị chỉnh sửa sau này.
        $plan = array_slice($plan, 0, self::TOTAL_RECORDS);
        while (count($plan) < self::TOTAL_RECORDS) {
            $plan[] = 'APPROVED';
        }

        shuffle($plan);

        return $plan;
    }

    private function pickContactRelation(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 60 => 'PARENT',
            $roll <= 75 => 'LEGAL_GUARDIAN',
            default => 'RELATIVE_OTHER',
        };
    }

    /**
     * @return array{0: ?\Illuminate\Support\Carbon, 1: ?int, 2: ?\Illuminate\Support\Carbon, 3: ?string}
     */
    private function statusTimestamps(string $status, $createdAt, int $moderatorId): array
    {
        $approvedAt = null;
        $approvedBy = null;
        $foundAt = null;
        $rejectionReason = null;

        if (in_array($status, ['APPROVED', 'FOUND', 'CLOSED'], true)) {
            $approvedAt = $createdAt->copy()->addHours(random_int(1, 12));
            $approvedBy = $moderatorId;
        }

        if (in_array($status, ['FOUND', 'CLOSED'], true)) {
            $foundAt = $approvedAt->copy()->addDays(random_int(1, 30));
        }

        if ($status === 'REJECTED') {
            $rejectionReason = 'Thông tin chưa đầy đủ hoặc chưa xác minh được, vui lòng bổ sung và gửi lại.';
        }

        return [$approvedAt, $approvedBy, $foundAt, $rejectionReason];
    }

    private function attachPhoto(int $childId, string $avatarBase, $takenAt, bool $isPrimary, int $sortOrder): void
    {
        DB::table('photos')->insert([
            'model_type' => 'App\\Models\\MissingChild',
            'model_id' => $childId,
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
            'is_primary' => $isPrimary ? 1 : 0,
            'sort_order' => $sortOrder,
            'created_at' => $takenAt,
            'updated_at' => $takenAt,
        ]);
    }

    /**
     * @return array{neutral: array<int, string>, happy: array<int, string>}
     */
    private function avatarSet(string $genderPrefix): array
    {
        $neutral = [];
        $happy = [];

        for ($i = 1; $i <= 4; $i++) {
            $neutral[] = sprintf('avatar_%s_neutral_%02d', $genderPrefix, $i);
            $happy[] = sprintf('avatar_%s_happy_%02d', $genderPrefix, $i);
        }

        return ['neutral' => $neutral, 'happy' => $happy];
    }

    /**
     * @return array<int, User>
     */
    private function ensureDemoReporters(): array
    {
        $names = [
            ['email' => 'demo.reporter1@timtre.vn', 'gender' => 'FEMALE', 'kyc' => 'VERIFIED'],
            ['email' => 'demo.reporter2@timtre.vn', 'gender' => 'MALE', 'kyc' => 'VERIFIED'],
            ['email' => 'demo.reporter3@timtre.vn', 'gender' => 'FEMALE', 'kyc' => 'VERIFIED'],
            ['email' => 'demo.reporter4@timtre.vn', 'gender' => 'MALE', 'kyc' => 'NONE'],
            ['email' => 'demo.reporter5@timtre.vn', 'gender' => 'FEMALE', 'kyc' => 'NONE'],
            ['email' => 'demo.reporter6@timtre.vn', 'gender' => 'MALE', 'kyc' => 'PENDING'],
        ];

        $reporters = [];

        foreach ($names as $i => $spec) {
            $reporters[] = User::firstOrCreate(
                ['email' => $spec['email']],
                [
                    'full_name' => $this->randomFullName($spec['gender']),
                    'phone' => $this->randomPhone(),
                    'password' => Hash::make('Demo@123456'),
                    'role' => 'USER',
                    'status' => 'ACTIVE',
                    'kyc_status' => $spec['kyc'],
                    'email_verified_at' => now(),
                ]
            );
        }

        return $reporters;
    }

    private function ensureModerator(): User
    {
        return User::where('role', 'MODERATOR')->inRandomOrder()->first()
            ?? User::where('role', 'ADMIN')->inRandomOrder()->first()
            ?? User::firstOrCreate(
                ['email' => 'demo.moderator@timtre.vn'],
                [
                    'full_name' => 'Kiểm Duyệt Demo',
                    'phone' => $this->randomPhone(),
                    'password' => Hash::make('Demo@123456'),
                    'role' => 'MODERATOR',
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(),
                ]
            );
    }
}
