<?php

namespace Database\Seeders\Concerns;

/**
 * Sinh dữ liệu demo tiếng Việt thực tế (tên, địa chỉ, mô tả...) để tránh
 * hiển thị "Lorem ipsum" hay tên Faker mặc định tiếng Anh khi demo.
 *
 * Dùng chung cho DemoMissingChildrenSeeder & DemoFoundChildrenSeeder (S6-A01).
 */
trait GeneratesVietnameseDemoData
{
    /** @var array<int, string> */
    protected array $familyNames = [
        'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan',
        'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý',
    ];

    /** @var array<int, string> */
    protected array $middleNamesMale = ['Văn', 'Minh', 'Quốc', 'Đức', 'Hữu', 'Anh', 'Thành', 'Xuân'];

    /** @var array<int, string> */
    protected array $middleNamesFemale = ['Thị', 'Ngọc', 'Thu', 'Thanh', 'Hồng', 'Kim', 'Bích', 'Diễm'];

    /** @var array<int, string> */
    protected array $givenNamesMale = [
        'An', 'Bình', 'Cường', 'Dũng', 'Đạt', 'Hải', 'Huy', 'Khang',
        'Khôi', 'Kiên', 'Long', 'Minh', 'Nam', 'Phong', 'Quang', 'Sơn',
        'Thắng', 'Tuấn', 'Việt', 'Vinh',
    ];

    /** @var array<int, string> */
    protected array $givenNamesFemale = [
        'Anh', 'Chi', 'Diệp', 'Giang', 'Hà', 'Hoa', 'Huyền', 'Lan',
        'Linh', 'Mai', 'My', 'Ngân', 'Nga', 'Nhi', 'Phương', 'Quyên',
        'Thảo', 'Trang', 'Trâm', 'Vy',
    ];

    /**
     * 5 tỉnh/thành dùng cho demo — khớp với O-01 (HandoverPointsSeeder):
     * Hà Nội, TP Hải Phòng, TP Đà Nẵng, TP Hồ Chí Minh, TP Cần Thơ.
     *
     * @return array<int, array{name: string, areas: array<int, array{district: string, ward: string, lat: float, lng: float}>}>
     */
    protected function demoProvinces(): array
    {
        return [
            1 => [
                'name' => 'Hà Nội',
                'areas' => [
                    ['district' => 'Hoàn Kiếm', 'ward' => 'Phường Hàng Bài', 'lat' => 21.0285, 'lng' => 105.8542],
                    ['district' => 'Cầu Giấy', 'ward' => 'Phường Dịch Vọng', 'lat' => 21.0362, 'lng' => 105.7907],
                    ['district' => 'Tây Hồ', 'ward' => 'Phường Nhật Tân', 'lat' => 21.0708, 'lng' => 105.8232],
                    ['district' => 'Đống Đa', 'ward' => 'Phường Kim Liên', 'lat' => 21.0119, 'lng' => 105.8264],
                    ['district' => 'Long Biên', 'ward' => 'Phường Bồ Đề', 'lat' => 21.0453, 'lng' => 105.8890],
                ],
            ],
            18 => [
                'name' => 'TP Hải Phòng',
                'areas' => [
                    ['district' => 'Ngô Quyền', 'ward' => 'Phường Máy Tơ', 'lat' => 20.8586, 'lng' => 106.6890],
                    ['district' => 'Hồng Bàng', 'ward' => 'Phường Hoàng Văn Thụ', 'lat' => 20.8600, 'lng' => 106.6822],
                    ['district' => 'Lê Chân', 'ward' => 'Phường An Biên', 'lat' => 20.8480, 'lng' => 106.6740],
                    ['district' => 'Kiến An', 'ward' => 'Phường Trần Thành Ngọ', 'lat' => 20.8028, 'lng' => 106.6229],
                ],
            ],
            21 => [
                'name' => 'TP Đà Nẵng',
                'areas' => [
                    ['district' => 'Hải Châu', 'ward' => 'Phường Hải Châu 1', 'lat' => 16.0678, 'lng' => 108.2208],
                    ['district' => 'Thanh Khê', 'ward' => 'Phường Thanh Khê Đông', 'lat' => 16.0668, 'lng' => 108.1897],
                    ['district' => 'Sơn Trà', 'ward' => 'Phường Mỹ An', 'lat' => 16.0505, 'lng' => 108.2437],
                    ['district' => 'Liên Chiểu', 'ward' => 'Phường Hòa Khánh', 'lat' => 16.0716, 'lng' => 108.1436],
                ],
            ],
            27 => [
                'name' => 'TP Hồ Chí Minh',
                'areas' => [
                    ['district' => 'Bình Thạnh', 'ward' => 'Phường 13', 'lat' => 10.8010, 'lng' => 106.7120],
                    ['district' => 'Quận 1', 'ward' => 'Phường Bến Nghé', 'lat' => 10.7756, 'lng' => 106.7019],
                    ['district' => 'Quận 7', 'ward' => 'Phường Tân Phú', 'lat' => 10.7302, 'lng' => 106.7152],
                    ['district' => 'Gò Vấp', 'ward' => 'Phường 6', 'lat' => 10.8386, 'lng' => 106.6650],
                    ['district' => 'Tân Bình', 'ward' => 'Phường 4', 'lat' => 10.8010, 'lng' => 106.6530],
                ],
            ],
            30 => [
                'name' => 'TP Cần Thơ',
                'areas' => [
                    ['district' => 'Ninh Kiều', 'ward' => 'Phường An Hòa', 'lat' => 10.0342, 'lng' => 105.7882],
                    ['district' => 'Ninh Kiều', 'ward' => 'Phường An Phú', 'lat' => 10.0294, 'lng' => 105.7690],
                    ['district' => 'Bình Thủy', 'ward' => 'Phường Long Hòa', 'lat' => 10.0577, 'lng' => 105.7269],
                    ['district' => 'Cái Răng', 'ward' => 'Phường Lê Bình', 'lat' => 9.9976, 'lng' => 105.7554],
                ],
            ],
        ];
    }

    protected function randomFullName(string $gender): string
    {
        $family = $this->familyNames[array_rand($this->familyNames)];

        if ($gender === 'FEMALE') {
            $middle = $this->middleNamesFemale[array_rand($this->middleNamesFemale)];
            $given = $this->givenNamesFemale[array_rand($this->givenNamesFemale)];
        } else {
            $middle = $this->middleNamesMale[array_rand($this->middleNamesMale)];
            $given = $this->givenNamesMale[array_rand($this->givenNamesMale)];
        }

        return "{$family} {$middle} {$given}";
    }

    protected function randomAdultName(string $gender = 'FEMALE'): string
    {
        return $this->randomFullName($gender);
    }

    /** @return array<int, string> */
    protected function clothesDescPool(): array
    {
        return [
            'Áo thun trắng in hình siêu nhân, quần short xanh navy',
            'Váy hoa màu vàng, áo khoác hồng nhạt, đeo ba lô màu tím',
            'Áo phông đỏ, quần dài xám, giày thể thao trắng',
            'Đồng phục học sinh trắng xanh, cặp sách hồng có hình thỏ',
            'Áo len xanh lá, quần jeans xanh đậm',
            'Áo khoác đen, quần jeans, đeo bông tai hình ngôi sao',
            'Áo ba lỗ xám, quần short kaki, dép tổ ong xanh',
            'Đầm hồng phấn, giày búp bê trắng, tóc buộc nơ đỏ',
            'Áo sơ mi caro, quần tây xanh navy, đeo kính cận',
            'Áo hoodie xám, quần thể thao đen, giày sneaker xanh',
        ];
    }

    /** @return array<int, string> */
    protected function specialMarksPool(): array
    {
        return [
            'Có sẹo nhỏ trên trán bên trái',
            'Nốt ruồi dưới cằm bên phải',
            'Mang kính cận, tóc dài buộc đuôi ngựa',
            'Có bớt nhỏ ở cánh tay trái',
            'Răng cửa bị sứt nhẹ, hay cười nhiều',
            'Tóc ngắn húi cua, da ngăm đen',
            'Đeo vòng tay bạc có khắc tên',
            null,
            null,
        ];
    }

    /** @return array<int, string> */
    protected function circumstancesPool(): array
    {
        return [
            'Trẻ được gửi ở nhà hàng xóm, phát hiện mất tích khi người thân đến đón',
            'Trẻ đi chơi cùng bạn, bạn về nhà mà không thấy trẻ',
            'Trẻ đi theo một người lạ tại khu vực chợ khi người lớn đang mua hàng',
            'Không thấy trẻ về nhà sau khi tan học',
            'Trẻ ra ngoài chơi buổi tối và không quay về',
            'Gia đình phát hiện trẻ mất tích sau giờ học thêm buổi chiều',
            'Trẻ đi lạc khi theo người thân đến khu vực đông người, hội chợ',
            'Trẻ tự ý rời nhà sau một cuộc tranh cãi với gia đình',
        ];
    }

    /** @return array<int, string> */
    protected function conditionDescPool(): array
    {
        return [
            'Trẻ được người dân phát hiện, sức khỏe ổn định, đang được chăm sóc tạm thời',
            'Phát hiện trẻ đi lạc tại khu vực công cộng, đã được đưa về điểm an toàn',
            'Trẻ có dấu hiệu hoảng loạn nhẹ, đã được trấn an và chăm sóc ban đầu',
            'Trẻ được đưa đến cơ sở y tế kiểm tra sức khỏe tổng quát, tình trạng ổn định',
            'Trẻ đói và mệt nhưng không có dấu hiệu bị thương, đang chờ xác minh thân nhân',
        ];
    }

    protected function randomPhone(): string
    {
        return '+8490' . random_int(1000000, 9999999);
    }
}
