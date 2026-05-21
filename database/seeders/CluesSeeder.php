<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CluesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('clues')->insert([
            // ── Clues cho case 1: Nguyễn Văn An ──────────────────────────────
            [
                'missing_id' => 1,
                'user_id' => 6,     // Bùi Thị Hoa
                'clue_type' => 'SIGHTING',
                'description' => 'Tôi thấy một bé trai mặc áo trắng đi một mình tại khu vực ngã tư Đinh Tiên Hoàng - Bạch Đằng lúc khoảng 15h, bé có vẻ bơ vơ và đang khóc.',
                'lat' => 10.8025000,
                'lng' => 106.7145000,
                'gps_shared_at' => $now->copy()->subDays(2),
                'location_accuracy_m' => 15,
                'occurred_at' => $now->copy()->subDays(2)->setTime(15, 0, 0),
                'image_path' => null,
                'is_verified' => 1,
                'verified_by' => 2,     // mod1
                'is_anonymous' => 0,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'missing_id' => 1,
                'user_id' => null,  // Ẩn danh
                'clue_type' => 'RUMOR',
                'description' => 'Nghe hàng xóm nói có thấy một bé trai hay chơi tại khu vực công viên Gia Định, nhưng không chắc có phải cháu này không.',
                'lat' => null,
                'lng' => null,
                'gps_shared_at' => null,
                'location_accuracy_m' => null,
                'occurred_at' => null,
                'image_path' => null,
                'is_verified' => 0,
                'verified_by' => null,
                'is_anonymous' => 1,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'missing_id' => 1,
                'user_id' => 7,     // Hoàng Văn Khoa (PENDING user)
                'clue_type' => 'PHOTO',
                'description' => 'Tôi chụp được ảnh một bé trai có nét giống trong thông báo đang đứng ở cổng trường tiểu học Đinh Tiên Hoàng lúc 16h30.',
                'lat' => 10.8050000,
                'lng' => 106.7100000,
                'gps_shared_at' => $now->copy()->subDays(1),
                'location_accuracy_m' => 20,
                'occurred_at' => $now->copy()->subDays(1)->setTime(16, 30, 0),
                'image_path' => 'clues/1/photo_clue_001.jpg',
                'is_verified' => 0,
                'verified_by' => null,
                'is_anonymous' => 0,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            // ── Clues cho case 2: Trần Thị Bích Ngọc ─────────────────────────
            [
                'missing_id' => 2,
                'user_id' => 4,     // Phạm Văn Bình
                'clue_type' => 'SIGHTING',
                'description' => 'Tôi thấy một bé gái mang kính và ba lô màu tím đứng gần cổng Nhà thờ Lớn Hà Nội vào khoảng 17h30, bé đang nhìn điện thoại.',
                'lat' => 21.0278000,
                'lng' => 105.8498000,
                'gps_shared_at' => $now->copy()->subDays(1),
                'location_accuracy_m' => 10,
                'occurred_at' => $now->copy()->subDays(1)->setTime(17, 30, 0),
                'image_path' => null,
                'is_verified' => 1,
                'verified_by' => 2,
                'is_anonymous' => 0,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subHours(12),
            ],
            [
                'missing_id' => 2,
                'user_id' => null,
                'clue_type' => 'VIDEO',
                'description' => 'Camera an ninh cửa hàng của tôi có thể đã ghi lại hình ảnh một bé gái đi qua vào khoảng 17h15, bé đội mũ và đeo ba lô. Tôi đã xuất file và gửi cho cơ quan chức năng.',
                'lat' => 21.0290000,
                'lng' => 105.8520000,
                'gps_shared_at' => $now->copy()->subDays(1),
                'location_accuracy_m' => 5,
                'occurred_at' => $now->copy()->subDays(1)->setTime(17, 15, 0),
                'image_path' => 'clues/2/cctv_still_001.jpg',
                'is_verified' => 0,
                'verified_by' => null,
                'is_anonymous' => 1,
                'created_at' => $now->copy()->subHours(18),
                'updated_at' => $now->copy()->subHours(18),
            ],
            // ── Clues cho case 3: Võ Quốc Hùng (FOUND) ──────────────────────
            [
                'missing_id' => 3,
                'user_id' => 5,     // Nguyễn Thị Lan
                'clue_type' => 'SIGHTING',
                'description' => 'Tôi thấy một bé trai mặc áo đỏ đang được một phụ nữ trung niên dẫn đi gần siêu thị Coopmart Biên Hòa, bé có vẻ buồn và không nói chuyện.',
                'lat' => 10.9600000,
                'lng' => 106.8400000,
                'gps_shared_at' => $now->copy()->subDays(10),
                'location_accuracy_m' => 25,
                'occurred_at' => $now->copy()->subDays(10)->setTime(10, 0, 0),
                'image_path' => null,
                'is_verified' => 1,
                'verified_by' => 3,     // mod2
                'is_anonymous' => 0,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'missing_id' => 3,
                'user_id' => null,
                'clue_type' => 'OTHER',
                'description' => 'Theo thông tin tôi được biết, có một bé trai được tìm thấy tại khu nhà trọ đường Võ Thị Sáu, phường Thống Nhất, Biên Hòa. Tôi đã báo cho công an phường.',
                'lat' => 10.9580000,
                'lng' => 106.8350000,
                'gps_shared_at' => $now->copy()->subDays(6),
                'location_accuracy_m' => 50,
                'occurred_at' => $now->copy()->subDays(6),
                'image_path' => null,
                'is_verified' => 1,
                'verified_by' => 3,
                'is_anonymous' => 1,
                'created_at' => $now->copy()->subDays(6),
                'updated_at' => $now->copy()->subDays(5),
            ],
        ]);
    }
}
