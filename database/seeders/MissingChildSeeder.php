<?php

namespace Database\Seeders;

use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Database\Seeder;

class MissingChildSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@timkiemtreem.vn')->first();

        $cases = [
            [
                'user_id' => $admin->id,
                'province_id' => 27, // TP Hồ Chí Minh
                'case_code' => 'MC-2025-001',
                'full_name' => 'Nguyễn Văn An',
                'gender' => 'MALE',
                'age_estimate' => 8,
                'missing_date' => now()->subDays(10)->toDateString(),
                'missing_time' => '14:30',
                'district' => 'Quận 6',
                'ward' => 'Phường Bình Tây',
                'address_detail' => 'Gần chợ Bình Tây',
                'circumstances' => 'Bé đi cùng mẹ ra chợ thì bị lạc trong khu vực đông người.',
                'clothes_desc' => 'Áo thun xanh navy, quần short đen, dép tổ ong vàng',
                'special_marks' => 'Có sẹo nhỏ ở cằm, tóc ngắn húi cua',
                'height_cm' => 125,
                'weight_kg' => 27,
                'contact_name' => 'Nguyễn Thị Lan (mẹ)',
                'contact_phone' => '0901234567',
                'contact_relation' => 'PARENT',
                'status' => 'APPROVED',
                'lat' => 10.7553,
                'lng' => 106.6608,
            ],
            [
                'user_id' => $admin->id,
                'province_id' => 1, // Hà Nội
                'case_code' => 'MC-2025-002',
                'full_name' => 'Trần Thị Linh',
                'gender' => 'FEMALE',
                'age_estimate' => 6,
                'missing_date' => now()->subDays(7)->toDateString(),
                'missing_time' => '16:00',
                'district' => 'Tây Hồ',
                'ward' => 'Phường Nhật Tân',
                'address_detail' => 'Khu vực Hồ Tây',
                'circumstances' => 'Bé đang chơi gần hồ cùng ông nội thì mất dấu.',
                'clothes_desc' => 'Váy hoa màu hồng, áo khoác trắng, giày búp bê',
                'special_marks' => 'Tóc dài buộc đuôi ngựa, có nốt ruồi ở má trái',
                'height_cm' => 112,
                'weight_kg' => 20,
                'contact_name' => 'Trần Văn Bình (bố)',
                'contact_phone' => '0912345678',
                'contact_relation' => 'PARENT',
                'status' => 'APPROVED',
                'lat' => 21.0504,
                'lng' => 105.8122,
            ],
            [
                'user_id' => $admin->id,
                'province_id' => 21, // Đà Nẵng
                'case_code' => 'MC-2025-003',
                'full_name' => 'Phạm Minh Đức',
                'gender' => 'MALE',
                'age_estimate' => 12,
                'missing_date' => now()->subDays(5)->toDateString(),
                'missing_time' => '08:00',
                'district' => 'Sơn Trà',
                'ward' => 'Phường Mỹ Khê',
                'address_detail' => 'Bãi biển Mỹ Khê',
                'circumstances' => 'Bé đi tắm biển cùng nhóm bạn, bị sóng cuốn và mất liên lạc.',
                'clothes_desc' => 'Quần bơi xanh lá, áo phao cam',
                'special_marks' => 'Cao lớn so với tuổi, da ngăm',
                'height_cm' => 148,
                'weight_kg' => 42,
                'contact_name' => 'Phạm Thị Hương (mẹ)',
                'contact_phone' => '0934567890',
                'contact_relation' => 'PARENT',
                'status' => 'APPROVED',
                'lat' => 16.0689,
                'lng' => 108.2452,
            ],
            [
                'user_id' => $admin->id,
                'province_id' => 30, // Cần Thơ
                'case_code' => 'MC-2025-004',
                'full_name' => 'Lê Thị Hoa',
                'gender' => 'FEMALE',
                'age_estimate' => 9,
                'missing_date' => now()->subDays(3)->toDateString(),
                'missing_time' => '17:30',
                'district' => 'Ninh Kiều',
                'ward' => 'Phường An Hòa',
                'address_detail' => 'Gần bến Ninh Kiều',
                'circumstances' => 'Bé đi bộ từ trường về nhà một mình và không về đến nơi.',
                'clothes_desc' => 'Đồng phục học sinh trắng xanh, cặp sách hồng có hình thỏ',
                'special_marks' => 'Đeo kính cận, tóc ngắn ngang vai',
                'height_cm' => 130,
                'weight_kg' => 30,
                'contact_name' => 'Lê Văn Tài (bố)',
                'contact_phone' => '0978901234',
                'contact_relation' => 'PARENT',
                'status' => 'APPROVED',
                'lat' => 10.0336,
                'lng' => 105.7876,
            ],
            [
                'user_id' => $admin->id,
                'province_id' => 28, // Đồng Nai
                'case_code' => 'MC-2025-005',
                'full_name' => 'Ngô Tuấn Kiệt',
                'gender' => 'MALE',
                'age_estimate' => 7,
                'missing_date' => now()->subDays(14)->toDateString(),
                'missing_time' => '09:15',
                'district' => 'Biên Hòa',
                'ward' => 'Phường Tam Hòa',
                'address_detail' => 'Khu vực KCN VSIP 2',
                'circumstances' => 'Bé đi lạc khi theo bố mẹ đến khu công nghiệp thăm người thân.',
                'clothes_desc' => 'Áo thun đỏ in logo bóng đá, quần dài xanh đậm, giày thể thao trắng',
                'special_marks' => 'Có răng cửa bị sứt nhẹ, hay cười nhiều',
                'height_cm' => 118,
                'weight_kg' => 22,
                'contact_name' => 'Ngô Thị Mai (mẹ)',
                'contact_phone' => '0956789012',
                'contact_relation' => 'PARENT',
                'status' => 'APPROVED',
                'lat' => 11.0558,
                'lng' => 106.6800,
            ],
        ];

        foreach ($cases as $case) {
            MissingChild::updateOrCreate(
                ['case_code' => $case['case_code']],
                $case
            );
        }
    }
}