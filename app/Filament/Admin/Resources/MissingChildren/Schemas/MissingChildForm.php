<?php

namespace App\Filament\Admin\Resources\MissingChildren\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class MissingChildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('full_name')->label('Họ và tên')->required()->maxLength(100),
                            TextInput::make('case_code')->label('Mã hồ sơ')->maxLength(20),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('gender')->label('Giới tính')
                                ->options(['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Không rõ'])
                                ->required(),
                            TextInput::make('age_estimate')->label('Tuổi ước tính')->numeric()->minValue(0)->maxValue(18),
                            Select::make('status')->label('Trạng thái')
                                ->options([
                                    'PENDING' => 'Chờ duyệt',
                                    'APPROVED' => 'Đã duyệt',
                                    'FOUND' => 'Đã tìm thấy',
                                    'CLOSED' => 'Đóng',
                                    'REJECTED' => 'Từ chối',
                                ])->required(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('height_cm')->label('Chiều cao (cm)')->numeric(),
                            TextInput::make('weight_kg')->label('Cân nặng (kg)')->numeric(),
                        ]),
                    ]),

                Section::make('Địa điểm & thời gian mất tích')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('province_id')->label('Tỉnh thành')
                                ->relationship('province', 'name')->searchable()->required(),
                            TextInput::make('district')->label('Quận/Huyện'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('ward')->label('Phường/Xã'),
                            TextInput::make('address_detail')->label('Địa chỉ chi tiết'),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('missing_date')->label('Ngày mất tích')->required(),
                            TimePicker::make('missing_time')->label('Giờ mất tích'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('lat')->label('Vĩ độ')->numeric(),
                            TextInput::make('lng')->label('Kinh độ')->numeric(),
                        ]),
                    ]),

                Section::make('Đặc điểm nhận dạng')
                    ->schema([
                        Textarea::make('clothes_desc')->label('Trang phục khi mất tích')->rows(2),
                        Textarea::make('special_marks')->label('Dấu hiệu đặc biệt')->rows(2),
                        Textarea::make('circumstances')->label('Hoàn cảnh mất tích')->rows(3),
                    ]),

                Section::make('Thông tin liên hệ')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('contact_name')->label('Người liên hệ')->required(),
                            TextInput::make('contact_phone')->label('Số điện thoại')->required(),
                            TextInput::make('contact_relation')->label('Quan hệ với bé'),
                        ]),
                    ]),
            ]);
    }
}