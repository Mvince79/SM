<?php

namespace App\Filament\Admin\Resources\Clues\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ViewEntry;

class ClueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin manh mối')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('missingChild.full_name')
                                ->label('Hồ sơ mất tích'),
                            TextEntry::make('user.full_name')
                                ->label('Người gửi')
                                ->placeholder('Ẩn danh'),
                            TextEntry::make('clue_type')
                                ->label('Loại manh mối')
                                ->badge()
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'SIGHTING' => 'Mục kích',
                                    'RUMOR' => 'Tin đồn',
                                    'PHOTO' => 'Hình ảnh',
                                    'VIDEO' => 'Video',
                                    'OTHER' => 'Khác',
                                    default => $state,
                                }),
                        ]),
                        TextEntry::make('description')
                            ->label('Mô tả chi tiết'),
                        Grid::make(2)->schema([
                            TextEntry::make('occurred_at')
                                ->label('Thời điểm xảy ra')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('created_at')
                                ->label('Ngày gửi')
                                ->dateTime('d/m/Y H:i'),
                        ]),
                    ]),

                Section::make('Vị trí GPS')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('lat')->label('Vĩ độ')->placeholder('—'),
                            TextEntry::make('lng')->label('Kinh độ')->placeholder('—'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('gps_shared_at')
                                ->label('Thời điểm chia sẻ GPS')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('location_accuracy_m')
                                ->label('Độ chính xác (mét)')
                                ->placeholder('—'),
                        ]),

                        ViewEntry::make('map')
                            ->label('Bản đồ')
                            ->view('filament.admin.clues.map')
                            ->columnSpanFull(),
                    ]),

                Section::make('Xác minh')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('is_verified')
                                ->label('Trạng thái')
                                ->badge()
                                ->color(fn($state) => $state ? 'success' : 'warning')
                                ->formatStateUsing(fn($state) => $state ? 'Đã xác minh' : 'Chưa xác minh'),
                            TextEntry::make('verifiedBy.full_name')
                                ->label('Admin xác minh')
                                ->placeholder('—'),
                            TextEntry::make('is_anonymous')
                                ->label('Ẩn danh')
                                ->badge()
                                ->formatStateUsing(fn($state) => $state ? 'Ẩn danh' : 'Có danh tính'),
                        ]),
                        ImageEntry::make('image_path')
                            ->label('Ảnh đính kèm')
                            ->placeholder('Không có ảnh'),
                    ]),
            ]);
    }
}
