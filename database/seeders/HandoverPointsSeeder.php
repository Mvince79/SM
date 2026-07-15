<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HandoverPointsSeeder extends Seeder
{
    public function run(): void
    {
        $provinceId = fn(string $name) => DB::table('provinces')->where('name', $name)->value('id');

        $now = now();

        $points = [
            // Tổng đài quốc gia bảo vệ trẻ em — DUY NHẤT 1 bản ghi, dùng chung mọi tỉnh
            [
                'type' => 'HOTLINE_111',
                'name' => 'Tổng đài Quốc gia Bảo vệ Trẻ em 111',
                'address' => 'Toàn quốc',
                'province_id' => $provinceId('Hà Nội'),
                'lat' => 21.0285,
                'lng' => 105.8542,
                'hotline_phone' => '111',
                'responsible_org_name' => 'Cục Trẻ em - Bộ Lao động Thương binh và Xã hội',
                'legal_authority_note' => null,
                'priority_order' => 0,
            ],

            // Hà Nội (3 điểm)
            [
                'type' => 'POLICE_STATION',
                'name' => 'Công an phường Hàng Bạc',
                'address' => 'Số 20 Hàng Bạc, Hoàn Kiếm, Hà Nội',
                'province_id' => $provinceId('Hà Nội'),
                'lat' => 21.0332,
                'lng' => 105.8508,
                'hotline_phone' => '024.38255000',
                'responsible_org_name' => 'Công an phường Hàng Bạc, Q. Hoàn Kiếm',
                'legal_authority_note' => 'Xác nhận thẩm quyền tiếp nhận trẻ lạc theo phân cấp quận Hoàn Kiếm',
                'priority_order' => 1,
            ],
            [
                'type' => 'COMMUNE_PEOPLE_COMMITTEE',
                'name' => 'UBND phường Cầu Giấy',
                'address' => 'Số 36 Trần Thái Tông, Cầu Giấy, Hà Nội',
                'province_id' => $provinceId('Hà Nội'),
                'lat' => 21.0334,
                'lng' => 105.7947,
                'hotline_phone' => '024.37820000',
                'responsible_org_name' => 'UBND phường Cầu Giấy',
                'legal_authority_note' => 'Văn bản phân công tiếp nhận trẻ lạc số 12/UBND-LĐTBXH',
                'priority_order' => 1,
            ],
            [
                'type' => 'NGO',
                'name' => 'Trung tâm Bảo trợ Xã hội Hà Nội',
                'address' => 'Xã Tiên Dược, Sóc Sơn, Hà Nội',
                'province_id' => $provinceId('Hà Nội'),
                'lat' => 21.1974,
                'lng' => 105.8194,
                'hotline_phone' => '024.35812345',
                'responsible_org_name' => 'Trung tâm Bảo trợ Xã hội III Hà Nội',
                'legal_authority_note' => 'Giấy phép hoạt động bảo trợ trẻ em số 45/GP-SLĐTBXH',
                'priority_order' => 2,
            ],

            // TP Hồ Chí Minh (3 điểm) — tên đúng theo ProvincesSeeder: 'TP Hồ Chí Minh' (id=27, không dấu chấm)
            [
                'type' => 'POLICE_STATION',
                'name' => 'Công an phường Bến Nghé',
                'address' => 'Số 2 Nguyễn Văn Cừ, Quận 1, TP.HCM',
                'province_id' => $provinceId('TP Hồ Chí Minh'),
                'lat' => 10.7756,
                'lng' => 106.7019,
                'hotline_phone' => '028.38220000',
                'responsible_org_name' => 'Công an phường Bến Nghé, Q.1',
                'legal_authority_note' => 'Xác nhận thẩm quyền tiếp nhận trẻ lạc theo phân cấp Quận 1',
                'priority_order' => 1,
            ],
            [
                'type' => 'COMMUNE_PEOPLE_COMMITTEE',
                'name' => 'UBND phường Tân Định',
                'address' => 'Số 137 Pasteur, Quận 3, TP.HCM',
                'province_id' => $provinceId('TP Hồ Chí Minh'),
                'lat' => 10.7891,
                'lng' => 106.6917,
                'hotline_phone' => '028.38290000',
                'responsible_org_name' => 'UBND phường Tân Định',
                'legal_authority_note' => 'Văn bản phân công tiếp nhận trẻ lạc số 08/UBND-LĐTBXH',
                'priority_order' => 1,
            ],
            [
                'type' => 'NGO',
                'name' => 'Làng trẻ em SOS Gò Vấp',
                'address' => '89 Đường số 6, Gò Vấp, TP.HCM',
                'province_id' => $provinceId('TP Hồ Chí Minh'),
                'lat' => 10.8386,
                'lng' => 106.6650,
                'hotline_phone' => '028.38940006',
                'responsible_org_name' => 'Làng trẻ em SOS Gò Vấp',
                'legal_authority_note' => 'Giấy phép hoạt động bảo trợ trẻ em số 22/GP-SLĐTBXH',
                'priority_order' => 2,
            ],

            // TP Đà Nẵng (3 điểm) — tên đúng: 'TP Đà Nẵng' (id=21, đã sáp nhập Quảng Nam)
            [
                'type' => 'POLICE_STATION',
                'name' => 'Công an phường Hải Châu 1',
                'address' => 'Số 8 Bạch Đằng, Hải Châu, Đà Nẵng',
                'province_id' => $provinceId('TP Đà Nẵng'),
                'lat' => 16.0678,
                'lng' => 108.2208,
                'hotline_phone' => '0236.3822000',
                'responsible_org_name' => 'Công an phường Hải Châu 1',
                'legal_authority_note' => 'Xác nhận thẩm quyền tiếp nhận trẻ lạc theo phân cấp Q. Hải Châu',
                'priority_order' => 1,
            ],
            [
                'type' => 'COMMUNE_PEOPLE_COMMITTEE',
                'name' => 'UBND phường Thanh Khê Đông',
                'address' => 'Số 15 Trần Cao Vân, Thanh Khê, Đà Nẵng',
                'province_id' => $provinceId('TP Đà Nẵng'),
                'lat' => 16.0668,
                'lng' => 108.1897,
                'hotline_phone' => '0236.3812000',
                'responsible_org_name' => 'UBND phường Thanh Khê Đông',
                'legal_authority_note' => 'Văn bản phân công tiếp nhận trẻ lạc số 05/UBND-LĐTBXH',
                'priority_order' => 1,
            ],
            [
                'type' => 'NGO',
                'name' => 'Trung tâm Công tác Xã hội Đà Nẵng',
                'address' => '132 Ông Ích Khiêm, Hải Châu, Đà Nẵng',
                'province_id' => $provinceId('TP Đà Nẵng'),
                'lat' => 16.0655,
                'lng' => 108.2140,
                'hotline_phone' => '0236.3565777',
                'responsible_org_name' => 'Trung tâm Công tác Xã hội TP. Đà Nẵng',
                'legal_authority_note' => 'Giấy phép hoạt động bảo trợ trẻ em số 11/GP-SLĐTBXH',
                'priority_order' => 2,
            ],

            // TP Cần Thơ (3 điểm) — tên đúng: 'TP Cần Thơ' (id=30, đã sáp nhập Sóc Trăng + Hậu Giang)
            [
                'type' => 'POLICE_STATION',
                'name' => 'Công an phường Ninh Kiều',
                'address' => 'Số 1 Hòa Bình, Ninh Kiều, Cần Thơ',
                'province_id' => $provinceId('TP Cần Thơ'),
                'lat' => 10.0333,
                'lng' => 105.7833,
                'hotline_phone' => '0292.3820000',
                'responsible_org_name' => 'Công an quận Ninh Kiều',
                'legal_authority_note' => 'Xác nhận thẩm quyền tiếp nhận trẻ lạc theo phân cấp Q. Ninh Kiều',
                'priority_order' => 1,
            ],
            [
                'type' => 'COMMUNE_PEOPLE_COMMITTEE',
                'name' => 'UBND phường An Phú',
                'address' => 'Số 10 Trần Văn Hoài, Ninh Kiều, Cần Thơ',
                'province_id' => $provinceId('TP Cần Thơ'),
                'lat' => 10.0294,
                'lng' => 105.7690,
                'hotline_phone' => '0292.3812999',
                'responsible_org_name' => 'UBND phường An Phú',
                'legal_authority_note' => 'Văn bản phân công tiếp nhận trẻ lạc số 03/UBND-LĐTBXH',
                'priority_order' => 1,
            ],
            [
                'type' => 'NGO',
                'name' => 'Trung tâm Bảo trợ Xã hội Cần Thơ',
                'address' => 'Khu vực Bình Yên B, Long Hòa, Bình Thủy, Cần Thơ',
                'province_id' => $provinceId('TP Cần Thơ'),
                'lat' => 10.0577,
                'lng' => 105.7269,
                'hotline_phone' => '0292.3841234',
                'responsible_org_name' => 'Trung tâm Bảo trợ Xã hội TP. Cần Thơ',
                'legal_authority_note' => 'Giấy phép hoạt động bảo trợ trẻ em số 07/GP-SLĐTBXH',
                'priority_order' => 2,
            ],

            // TP Hải Phòng (2 điểm) — tên đúng: 'TP Hải Phòng' (id=18, đã sáp nhập Hải Dương)
            [
                'type' => 'POLICE_STATION',
                'name' => 'Công an phường Máy Tơ',
                'address' => 'Số 5 Điện Biên Phủ, Ngô Quyền, Hải Phòng',
                'province_id' => $provinceId('TP Hải Phòng'),
                'lat' => 20.8586,
                'lng' => 106.6890,
                'hotline_phone' => '0225.3822000',
                'responsible_org_name' => 'Công an phường Máy Tơ',
                'legal_authority_note' => 'Xác nhận thẩm quyền tiếp nhận trẻ lạc theo phân cấp Q. Ngô Quyền',
                'priority_order' => 1,
            ],
            [
                'type' => 'OTHER',
                'name' => 'Trung tâm Công tác Xã hội Hải Phòng',
                'address' => '154 Trần Phú, Ngô Quyền, Hải Phòng',
                'province_id' => $provinceId('TP Hải Phòng'),
                'lat' => 20.8515,
                'lng' => 106.6837,
                'hotline_phone' => '0225.3745678',
                'responsible_org_name' => 'Trung tâm Công tác Xã hội và Quỹ Bảo trợ trẻ em Hải Phòng',
                'legal_authority_note' => 'Giấy phép hoạt động bảo trợ trẻ em số 09/GP-SLĐTBXH',
                'priority_order' => 2,
            ],
        ];

        foreach ($points as $point) {
            if ($point['province_id'] === null) {
                throw new \RuntimeException(
                    "province_id null cho điểm '{$point['name']}' — kiểm tra lại tên tỉnh có khớp bảng provinces không."
                );
            }

            DB::table('handover_points')->insert(array_merge($point, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}