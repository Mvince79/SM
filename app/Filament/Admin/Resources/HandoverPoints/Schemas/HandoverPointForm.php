<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Schemas;

use App\Filament\Forms\Components\LocationPicker;
use App\Models\Province;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HandoverPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->components([
                Section::make('Văn bản xác nhận thẩm quyền')
                    ->description(
                        'Ảnh sẽ hiển thị bản xem trước; PDF có thể mở hoặc tải xuống.'
                    )
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        FileUpload::make('verification_document_path')
                            ->label('Văn bản xác nhận thẩm quyền')
                            ->helperText(
                                'Chấp nhận PDF, JPG, JPEG hoặc PNG. Dung lượng tối đa 5 MB.'
                            )
                            ->disk('public')
                            ->directory('handover-points/verification-documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->imagePreviewHeight('350')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Thông tin điểm bàn giao')
                    ->description(
                        'Thông tin đơn vị có thẩm quyền tiếp nhận và hỗ trợ bàn giao trẻ an toàn.'
                    )
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên điểm bàn giao')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ví dụ: Công an Phường An Khánh')
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Loại điểm bàn giao')
                            ->options([
                                'POLICE_STATION' => 'Công an',
                                'COMMUNE_PEOPLE_COMMITTEE' => 'UBND xã/phường',
                                'HOTLINE_111' => 'Tổng đài Quốc gia 111',
                                'NGO' => 'Tổ chức NGO',
                                'OTHER' => 'Đơn vị khác',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),

                        Select::make('province_id')
                            ->label('Tỉnh/Thành phố')
                            ->options(
                                fn(): array => Province::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),

                        TextInput::make('address')
                            ->label('Địa chỉ')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Nhập địa chỉ đầy đủ')
                            ->columnSpanFull(),

                        TextInput::make('hotline_phone')
                            ->label('Số điện thoại/Hotline')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Ví dụ: 111 hoặc 02923891234'),

                        TextInput::make('responsible_org_name')
                            ->label('Đơn vị chịu trách nhiệm')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ví dụ: Công an Thành phố Cần Thơ'),

                        Textarea::make('legal_authority_note')
                            ->label('Căn cứ/Xác nhận thẩm quyền')
                            ->helperText(
                                'Bắt buộc đối với các điểm bàn giao, ngoại trừ Tổng đài Quốc gia 111.'
                            )
                            ->required(
                                fn(Get $get): bool => $get('type') !== 'HOTLINE_111'
                            )
                            ->hidden(
                                fn(Get $get): bool => $get('type') === 'HOTLINE_111'
                            )
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Vị trí trên bản đồ')
                    ->description(
                        'Click vào bản đồ để chọn vị trí. Kinh độ và vĩ độ sẽ được cập nhật tự động.'
                    )
                    ->schema([
                        LocationPicker::make('location_picker')
                            ->label('Chọn vị trí')
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('lat')
                            ->label('Vĩ độ')
                            ->required()
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step('any')
                            ->placeholder('Ví dụ: 10.045162'),

                        TextInput::make('lng')
                            ->label('Kinh độ')
                            ->required()
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->step('any')
                            ->placeholder('Ví dụ: 105.746857'),
                    ])
                    ->columns(2),

                Section::make('Thiết lập hoạt động')
                    ->schema([
                        TextInput::make('priority_order')
                            ->label('Thứ tự ưu tiên')
                            ->helperText(
                                'Số nhỏ hơn sẽ được ưu tiên khi các điểm có khoảng cách gần bằng nhau.'
                            )
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->helperText(
                                'Chỉ các điểm đang hoạt động mới được hiển thị cho người dùng.'
                            )
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
