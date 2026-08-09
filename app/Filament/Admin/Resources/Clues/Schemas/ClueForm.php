<?php

namespace App\Filament\Admin\Resources\Clues\Schemas;

use App\Rules\SecureImage;
use App\Support\SecureUploadFilename;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ClueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin manh mối')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('missing_id')
                                ->label('Hồ sơ mất tích')
                                ->relationship('missingChild', 'full_name')
                                ->searchable()
                                ->required(),
                            Select::make('user_id')
                                ->label('Người gửi')
                                ->relationship('user', 'full_name')
                                ->searchable()
                                ->placeholder('Ẩn danh'),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('clue_type')
                                ->label('Loại manh mối')
                                ->options([
                                    'SIGHTING' => 'Mục kích trực tiếp',
                                    'RUMOR' => 'Tin đồn',
                                    'PHOTO' => 'Hình ảnh',
                                    'VIDEO' => 'Video',
                                    'OTHER' => 'Khác',
                                ])
                                ->default('SIGHTING')
                                ->required(),
                            DateTimePicker::make('occurred_at')
                                ->label('Thời điểm xảy ra'),
                        ]),
                        Textarea::make('description')
                            ->label('Mô tả chi tiết')
                            ->required()
                            ->rows(4),
                    ]),

                Section::make('Vị trí GPS')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('lat')->label('Vĩ độ')->numeric(),
                            TextInput::make('lng')->label('Kinh độ')->numeric(),
                        ]),
                        Grid::make(2)->schema([
                            DateTimePicker::make('gps_shared_at')->label('Thời điểm chia sẻ GPS'),
                            TextInput::make('location_accuracy_m')
                                ->label('Độ chính xác (mét)')
                                ->numeric(),
                        ]),
                    ]),

                Section::make('Hình ảnh & Xác minh')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Ảnh đính kèm')
                            ->image()
                            ->maxSize(5120)
                            ->rules([new SecureImage])
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => SecureUploadFilename::for($file),
                            ),
                        Grid::make(2)->schema([
                            Select::make('is_verified')
                                ->label('Trạng thái xác minh')
                                ->options(['0' => 'Chưa xác minh', '1' => 'Đã xác minh'])
                                ->default('0')
                                ->required(),
                            Select::make('verified_by')
                                ->label('Admin xác minh')
                                ->relationship('verifiedBy', 'full_name')
                                ->searchable()
                                ->placeholder('—'),
                        ]),
                        Select::make('is_anonymous')
                            ->label('Gửi ẩn danh')
                            ->options(['0' => 'Không', '1' => 'Có'])
                            ->default('0')
                            ->required(),
                    ]),
            ]);
    }
}
