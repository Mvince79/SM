<?php

namespace Database\Seeders;

use App\Models\FoundChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoundChildSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@timkiemtreem.vn'],
            [
                'full_name' => 'Hệ thống Admin',
                'phone' => '0905123456',
                'password' => bcrypt('Admin@123456'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        $reporter = User::firstOrCreate(
            ['email' => 'nguoidung.demo@timkiemtreem.vn'],
            [
                'full_name' => 'Người báo mẫu',
                'phone' => '0912345678',
                'password' => bcrypt('User@123456'),
                'role' => 'USER',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        $provinces = Province::query()->whereIn('id', [1, 2, 18, 21, 28, 34])->get()->keyBy('id');

        $records = [
            [
                'user_id' => $reporter->id,
                'province_id' => 1,
                'found_date' => now()->subDays(8)->toDateString(),
                'found_time' => '08:30',
                'gender_estimate' => 'MALE',
                'age_estimate' => 6,
                'condition_desc' => 'Trẻ được phát hiện tại khu vực bến xe, sức khỏe ổn định, đang được chăm sóc tạm thời.',
                'clothes_desc' => 'Áo thun màu xanh, quần short đen, dép nhựa đỏ.',
                'special_marks' => 'Có vết bớt nhỏ ở cánh tay trái.',
                'current_location' => 'Trạm bảo trợ xã hội Hà Nội',
                'handover_agency' => 'Công an phường sở tại',
                'status' => 'PENDING',
            ],
            [
                'user_id' => $admin->id,
                'province_id' => 21,
                'found_date' => now()->subDays(5)->toDateString(),
                'found_time' => '14:15',
                'gender_estimate' => 'FEMALE',
                'age_estimate' => 8,
                'condition_desc' => 'Phát hiện bé đi lạc tại trung tâm thương mại, đã được lực lượng hỗ trợ đưa về điểm an toàn.',
                'clothes_desc' => 'Đầm màu hồng, giày thể thao trắng, đeo balo nhỏ.',
                'special_marks' => 'Tóc buộc hai bên, có nốt ruồi ở má phải.',
                'current_location' => 'Trung tâm hỗ trợ trẻ em Đà Nẵng',
                'handover_agency' => 'Công an',
                'status' => 'APPROVED',
            ],
            [
                'user_id' => $reporter->id,
                'province_id' => 18,
                'found_date' => now()->subDays(3)->toDateString(),
                'found_time' => '19:00',
                'gender_estimate' => 'UNKNOWN',
                'age_estimate' => 5,
                'condition_desc' => 'Trẻ được người dân phát hiện gần công viên, hiện đang chờ xác minh thân nhân.',
                'clothes_desc' => 'Áo khoác xám, quần jeans xanh.',
                'special_marks' => 'Đeo vòng tay đỏ.',
                'current_location' => 'Nhà tạm lánh Hải Phòng',
                'handover_agency' => 'UBND phường',
                'status' => 'MATCHED',
            ],
        ];

        foreach ($records as $record) {
            if (! $provinces->has($record['province_id'])) {
                continue;
            }

            FoundChild::updateOrCreate(
                [
                    'user_id' => $record['user_id'],
                    'province_id' => $record['province_id'],
                    'found_date' => $record['found_date'],
                    'found_time' => $record['found_time'],
                ],
                $record
            );
        }
    }
}
