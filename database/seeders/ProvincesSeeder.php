<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvincesSeeder extends Seeder
{
    /**
     * Seed 34 đơn vị hành chính cấp tỉnh sau sáp nhập.
     *
     * Căn cứ pháp lý:
     *   - Nghị quyết số 202/2025/QH15 của Quốc hội khóa XV
     *   - Có hiệu lực từ ngày 12/06/2025, chính thức hoạt động từ 01/07/2025
     *   - Nguồn: chinhphu.vn, Nghị quyết 60-NQ/TW
     *
     * Cấu trúc sau sáp nhập:
     *   - 6 thành phố trực thuộc TW: Hà Nội, TP Hải Phòng, TP Đà Nẵng, TP Huế, TP HCM, TP Cần Thơ
     *   - 28 tỉnh còn lại
     *   - 11 tỉnh/thành giữ nguyên: Hà Nội, TP Huế, Cao Bằng, Lạng Sơn, Lai Châu,
     *                                Điện Biên, Sơn La, Quảng Ninh, Nghệ An, Thanh Hóa, Hà Tĩnh
     *   - 23 tỉnh/thành hình thành từ sáp nhập
     *
     * Lưu ý quan trọng về code CHAR(6):
     *   - VN-DN  (id=21) = TP Đà Nẵng  ≠  VN-DNA (id=28) = Đồng Nai
     *   - VN-HN  (id=1)  = Hà Nội      ≠  VN-HP  (id=18) = TP Hải Phòng
     *   - VN-HUE (id=2)  = TP Huế (6 ký tự — đúng CHAR(6))
     *   - VN-QT  (id=20) = Quảng Trị mới (Quảng Bình + Quảng Trị cũ) — KHÔNG phải Quảng Trị cũ
     *   - VN-QN  (id=3)  = Quảng Ninh  ≠  VN-QNG (id=22) = Quảng Ngãi
     */
    public function run(): void
    {
        DB::table('provinces')->upsert(
            $this->getData(),
            ['id'],
            ['name', 'code', 'short_code', 'region', 'is_active', 'updated_at']
        );
    }

    private function getData(): array
    {
        $now = now();

        return [
            // =====================================================================
            // MIỀN BẮC — 15 tỉnh/thành
            // =====================================================================

            [   // Giữ nguyên — không sáp nhập
                'id' => 1,
                'name' => 'Hà Nội',
                'code' => 'VN-HN',
                'short_code' => 'HAN',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 3,
                'name' => 'Quảng Ninh',
                'code' => 'VN-QN',
                'short_code' => 'QNI',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 4,
                'name' => 'Cao Bằng',
                'code' => 'VN-CB',
                'short_code' => 'CBA',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 5,
                'name' => 'Lạng Sơn',
                'code' => 'VN-LSN',
                'short_code' => 'LSN',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 6,
                'name' => 'Lai Châu',
                'code' => 'VN-LCH',
                'short_code' => 'LCH',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 7,
                'name' => 'Điện Biên',
                'code' => 'VN-DB',
                'short_code' => 'DBN',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 8,
                'name' => 'Sơn La',
                'code' => 'VN-SL',
                'short_code' => 'SLA',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Hà Giang + Tuyên Quang → Tuyên Quang
                'id' => 12,
                'name' => 'Tuyên Quang',
                'code' => 'VN-TQ',
                'short_code' => 'TQG',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Yên Bái + Lào Cai → Lào Cai
                'id' => 13,
                'name' => 'Lào Cai',
                'code' => 'VN-LC',
                'short_code' => 'LCA',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bắc Kạn + Thái Nguyên → Thái Nguyên
                'id' => 14,
                'name' => 'Thái Nguyên',
                'code' => 'VN-TN',
                'short_code' => 'TNG',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Vĩnh Phúc + Hòa Bình + Phú Thọ → Phú Thọ
                'id' => 15,
                'name' => 'Phú Thọ',
                'code' => 'VN-PT',
                'short_code' => 'PTH',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bắc Giang + Bắc Ninh → Bắc Ninh
                'id' => 16,
                'name' => 'Bắc Ninh',
                'code' => 'VN-BN',
                'short_code' => 'BNI',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Thái Bình + Hưng Yên → Hưng Yên
                // Căn cứ: khoản 6 Điều 1 Nghị quyết 202/2025/QH15
                // Hải Dương KHÔNG liên quan — Hải Dương sáp nhập vào TP Hải Phòng (id=18)
                'id' => 17,
                'name' => 'Hưng Yên',
                'code' => 'VN-HY',
                'short_code' => 'HYE',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Hải Dương + TP Hải Phòng → TP Hải Phòng
                'id' => 18,
                'name' => 'TP Hải Phòng',
                'code' => 'VN-HP',
                'short_code' => 'HPG',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Hà Nam + Nam Định + Ninh Bình → Ninh Bình
                'id' => 19,
                'name' => 'Ninh Bình',
                'code' => 'VN-NB',
                'short_code' => 'NBH',
                'region' => 'BAC',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =====================================================================
            // MIỀN TRUNG & TÂY NGUYÊN — 11 tỉnh/thành
            // =====================================================================

            [   // Giữ nguyên — không sáp nhập
                'id' => 2,
                'name' => 'TP Huế',
                'code' => 'VN-HUE',
                'short_code' => 'HUE',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 9,
                'name' => 'Thanh Hóa',
                'code' => 'VN-TH',
                'short_code' => 'THA',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 10,
                'name' => 'Nghệ An',
                'code' => 'VN-NA',
                'short_code' => 'NAN',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Giữ nguyên
                'id' => 11,
                'name' => 'Hà Tĩnh',
                'code' => 'VN-HT',
                'short_code' => 'HTI',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Quảng Bình + Quảng Trị → Quảng Trị
                // Tên tỉnh mới: Quảng Trị; trung tâm hành chính: TP Đồng Hới (Quảng Bình cũ)
                'id' => 20,
                'name' => 'Quảng Trị',
                'code' => 'VN-QT',
                'short_code' => 'QTR',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Quảng Nam + TP Đà Nẵng → TP Đà Nẵng
                // QUAN TRỌNG: VN-DN (Đà Nẵng) ≠ VN-DNA (Đồng Nai, id=28)
                'id' => 21,
                'name' => 'TP Đà Nẵng',
                'code' => 'VN-DN',
                'short_code' => 'DAN',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Kon Tum + Quảng Ngãi → Quảng Ngãi
                'id' => 22,
                'name' => 'Quảng Ngãi',
                'code' => 'VN-QNG',
                'short_code' => 'QNG',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bình Định + Gia Lai → Gia Lai
                'id' => 23,
                'name' => 'Gia Lai',
                'code' => 'VN-GL',
                'short_code' => 'GLA',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Ninh Thuận + Khánh Hòa → Khánh Hòa
                'id' => 24,
                'name' => 'Khánh Hòa',
                'code' => 'VN-KH',
                'short_code' => 'KHA',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Đắk Nông + Bình Thuận + Lâm Đồng → Lâm Đồng
                'id' => 25,
                'name' => 'Lâm Đồng',
                'code' => 'VN-LD',
                'short_code' => 'LDO',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Phú Yên + Đắk Lắk → Đắk Lắk
                'id' => 26,
                'name' => 'Đắk Lắk',
                'code' => 'VN-DL',
                'short_code' => 'DLK',
                'region' => 'TRUNG',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // =====================================================================
            // MIỀN NAM — 8 tỉnh/thành
            // =====================================================================

            [   // Sáp nhập: Bà Rịa-Vũng Tàu + Bình Dương + TP HCM → TP Hồ Chí Minh
                'id' => 27,
                'name' => 'TP Hồ Chí Minh',
                'code' => 'VN-HC',
                'short_code' => 'HCM',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bình Phước + Đồng Nai → Đồng Nai
                // QUAN TRỌNG: VN-DNA (Đồng Nai) ≠ VN-DN (TP Đà Nẵng, id=21)
                'id' => 28,
                'name' => 'Đồng Nai',
                'code' => 'VN-DNA',
                'short_code' => 'DNA',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Long An + Tây Ninh → Tây Ninh
                'id' => 29,
                'name' => 'Tây Ninh',
                'code' => 'VN-TAY',
                'short_code' => 'TAY',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Sóc Trăng + Hậu Giang + TP Cần Thơ → TP Cần Thơ
                'id' => 30,
                'name' => 'TP Cần Thơ',
                'code' => 'VN-CT',
                'short_code' => 'CTO',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bến Tre + Trà Vinh + Vĩnh Long → Vĩnh Long
                'id' => 31,
                'name' => 'Vĩnh Long',
                'code' => 'VN-VL',
                'short_code' => 'VLG',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Tiền Giang + Đồng Tháp → Đồng Tháp
                'id' => 32,
                'name' => 'Đồng Tháp',
                'code' => 'VN-DTP',
                'short_code' => 'DTP',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Bạc Liêu + Cà Mau → Cà Mau
                'id' => 33,
                'name' => 'Cà Mau',
                'code' => 'VN-CM',
                'short_code' => 'CMU',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [   // Sáp nhập: Kiên Giang + An Giang → An Giang
                'id' => 34,
                'name' => 'An Giang',
                'code' => 'VN-AG',
                'short_code' => 'AGI',
                'region' => 'NAM',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }
}