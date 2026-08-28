<?php

namespace App\Filament\Admin\Resources\MissingChildren\Schemas;

use App\Filament\Forms\Components\LocationPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MissingChildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ══════════════════════════════════════════════════════════════
            // BLOCK 1 — Thông tin cơ bản (full width)
            // ══════════════════════════════════════════════════════════════
            Section::make('Thông tin cơ bản')
                ->icon('heroicon-o-identification')
                ->schema([

                    // Họ tên (2/3) + Trạng thái (1/3)
                    Grid::make(3)->schema([
                        TextInput::make('full_name')
                            ->label('Họ và tên trẻ')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Nhập họ tên đầy đủ...')
                            ->columnSpan(2),

                        Select::make('status')
                            ->label('Trạng thái hồ sơ')
                            ->required()
                            ->options([
                                'PENDING' => 'Chờ duyệt',
                                'APPROVED' => 'Đã duyệt',
                                'FOUND' => 'Đã tìm thấy',
                                'CLOSED' => 'Đã đóng',
                                'REJECTED' => 'Từ chối',
                            ])
                            ->columnSpan(1),
                    ]),

                    // Mã hồ sơ + Giới tính + Màu da — 3 cột đều nhau
                    Grid::make(3)->schema([
                        TextInput::make('case_code')
                            ->label('Mã hồ sơ')
                            ->maxLength(20)
                            ->placeholder('Tự động sinh nếu để trống')
                            ->disabled(fn($record) => $record !== null)
                            ->helperText('Không thể thay đổi sau khi tạo'),

                        Select::make('gender')
                            ->label('Giới tính')
                            ->required()
                            ->options([
                                'MALE' => 'Nam',
                                'FEMALE' => 'Nữ',
                                'UNKNOWN' => 'Không rõ',
                            ]),

                        Select::make('skin_tone')
                            ->label('Màu da')
                            ->options([
                                'LIGHT' => 'Sáng',
                                'MEDIUM' => 'Trung bình',
                                'DARK' => 'Ngăm',
                            ])
                            ->placeholder('Chọn màu da'),
                    ]),

                    // Tuổi / Chiều cao / Cân nặng — 3 cột, đủ rộng nhập số
                    Grid::make(3)->schema([
                        TextInput::make('age_estimate')
                            ->label('Tuổi ước tính')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(18)
                            ->suffix('tuổi')
                            ->placeholder('Ví dụ: 7'),

                        TextInput::make('height_cm')
                            ->label('Chiều cao')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(250)
                            ->suffix('cm')
                            ->placeholder('Ví dụ: 120'),

                        TextInput::make('weight_kg')
                            ->label('Cân nặng')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(150)
                            ->suffix('kg')
                            ->placeholder('Ví dụ: 22'),
                    ]),

                ]),

            // ══════════════════════════════════════════════════════════════
            // BLOCK 2 — Đặc điểm nhận dạng (full width, textarea lớn)
            // ══════════════════════════════════════════════════════════════
            Section::make('Đặc điểm nhận dạng')
                ->icon('heroicon-o-eye')
                ->description('Mô tả càng chi tiết càng tốt — giúp cộng đồng nhận ra trẻ')
                ->schema([
                    Grid::make(3)->schema([
                        Textarea::make('clothes_desc')
                            ->label('Trang phục khi mất tích')
                            ->rows(4)
                            ->placeholder("Ví dụ:\nÁo thun đỏ in logo bóng đá\nQuần jean xanh đậm\nGiày thể thao trắng Nike")
                            ->maxLength(500),

                        Textarea::make('special_marks')
                            ->label('Dấu hiệu đặc biệt')
                            ->rows(4)
                            ->placeholder("Ví dụ:\nSẹo nhỏ ở cằm trái\nNốt ruồi ở má phải\nHay cười lộ răng")
                            ->maxLength(500),

                        Textarea::make('circumstances')
                            ->label('Hoàn cảnh mất tích')
                            ->rows(4)
                            ->placeholder("Ví dụ:\nBé đi học về lúc 16h\nKhông thấy về nhà từ 17h\nLần cuối thấy ở gần trường")
                            ->maxLength(1000),
                    ]),
                ]),

            // ══════════════════════════════════════════════════════════════
            // BLOCK 3 — Người liên hệ (sidebar) | Địa điểm & Thời gian
            // Flex để sidebar co đúng, không bị flatten
            // ══════════════════════════════════════════════════════════════
            Flex::make([

                Section::make('Người liên hệ')
                    ->icon('heroicon-o-phone')
                    ->grow(false)
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Họ tên người liên hệ')
                            ->required()
                            ->placeholder('Nguyễn Thị Mai'),

                        TextInput::make('contact_phone')
                            ->label('Số điện thoại')
                            ->required()
                            ->tel()
                            ->placeholder('0912 345 678'),

                        Select::make('contact_relation')
                            ->label('Quan hệ với bé')
                            ->options(\App\Models\MissingChild::CONTACT_RELATION_LABELS)
                            ->placeholder('Chọn mối quan hệ…')
                            ->native(false)
                            ->searchable(),
                    ]),

                Section::make('Địa điểm & thời gian mất tích')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([

                        Select::make('province_id')
                            ->label('Tỉnh / Thành phố')
                            ->required()
                            ->relationship('province', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Tìm tỉnh thành...'),

                        // V-01/V-02 — Xã/Phường KHÔNG phải input tự do: giá trị do
                        // reverse-geocode nội bộ suy ra từ lat/lng. Hiển thị chỉ đọc.
                        // Hành chính VN từ 01/07/2025 chỉ còn 2 cấp: Tỉnh → Xã/Phường.
                        Grid::make(2)->schema([
                            TextInput::make('ward')
                                ->label('Xã / Phường (tự động từ toạ độ)')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Xác định tự động từ vị trí ghim trên bản đồ'),
                        ]),

                        // V-01 — Ghi chú tuỳ chọn, KHÔNG phải địa chỉ hành chính chính thức.
                        TextInput::make('address_detail')
                            ->label('Ghi chú địa điểm (tùy chọn)')
                            ->maxLength(500)
                            ->helperText('Chỉ là mô tả thêm giúp nhận diện hiện trường. Địa chỉ hành chính chính thức được xác định tự động từ toạ độ.')
                            ->placeholder('Ví dụ: gần cổng sau chợ, cạnh trạm xe buýt...'),

                        // Ngày + Giờ trên 1 row — label ngắn, không bị squeeze
                        Grid::make(2)->schema([
                            DatePicker::make('missing_date')
                                ->label('Ngày mất tích')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            TimePicker::make('missing_time')
                                ->label('Giờ mất tích')
                                ->seconds(false)
                                ->placeholder('Không rõ'),
                        ]),

                    ]),

            ])->from('md'),

            // ────────────────────────────────────────────────────────────────
            // BLOCK 3b — Vị trí trên bản đồ (full-width, tách khỏi Flex bên trên
            // để không bị co hẹp vào cột hẹp gây khoảng trống)
            // ────────────────────────────────────────────────────────────────
            Section::make('Vị trí trên bản đồ')
                ->description('Chạm/click vào bản đồ hoặc kéo marker để chọn vị trí. Vĩ độ và kinh độ được cập nhật tự động.')
                ->schema([
                    LocationPicker::make('location_picker')
                        ->label('Chọn vị trí')
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('lat')
                            ->label('Vĩ độ')
                            ->numeric()
                            ->step('any')
                            ->placeholder('10.7769')
                            ->helperText('Cập nhật tự động từ bản đồ'),

                        TextInput::make('lng')
                            ->label('Kinh độ')
                            ->numeric()
                            ->step('any')
                            ->placeholder('106.7009'),
                    ]),
                ]),

        ]);
    }
}