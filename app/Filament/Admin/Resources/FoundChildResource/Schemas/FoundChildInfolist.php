<?php

namespace App\Filament\Admin\Resources\FoundChildResource\Schemas;

use App\Models\FoundChild;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FoundChildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        ViewEntry::make('hero_photo')
                            ->hiddenLabel()
                            ->state(function (FoundChild $record): array {
                                $photosCount = $record->photos()->count();

                                return [
                                    'url' => $record->primary_photo_url,
                                    'other_count' => max(0, $photosCount - 1),
                                ];
                            })
                            ->view('filament.infolists.found-child-hero-photo')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Tóm tắt hồ sơ')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('user.full_name')
                                ->label('Người báo')
                                ->placeholder('—'),
                            TextEntry::make('province.name')
                                ->label('Tỉnh/Thành')
                                ->placeholder('—'),
                            TextEntry::make('status')
                                ->label('Trạng thái')
                                ->badge()
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'PENDING' => 'Đang chờ',
                                    'APPROVED' => 'Đã duyệt',
                                    'MATCHED' => 'Đã khớp',
                                    'FOUND' => 'Đã tìm thấy',
                                    'REJECTED' => 'Bị từ chối',
                                    'ACTIVE' => 'Đang hoạt động',
                                    'RESOLVED' => 'Đã xử lý',
                                    'CLOSED' => 'Đã đóng',
                                    default => $state,
                                })
                                ->color(fn($state) => match ($state) {
                                    'PENDING' => 'warning',
                                    'APPROVED', 'MATCHED' => 'success',
                                    'FOUND' => 'info',
                                    'REJECTED' => 'danger',
                                    'ACTIVE' => 'primary',
                                    'RESOLVED' => 'gray',
                                    'CLOSED' => 'gray',
                                    default => 'gray',
                                }),
                            TextEntry::make('found_date')
                                ->label('Ngày phát hiện')
                                ->formatStateUsing(fn($state) => $state?->format('d/m/Y') ?? '—'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('found_time')
                                ->label('Giờ phát hiện')
                                ->placeholder('—'),
                            TextEntry::make('gender_estimate')
                                ->label('Giới tính ước tính')
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'MALE' => 'Nam',
                                    'FEMALE' => 'Nữ',
                                    'UNKNOWN' => 'Không rõ',
                                    default => '—',
                                }),
                            TextEntry::make('age_estimate')
                                ->label('Tuổi ước tính')
                                ->placeholder('—'),
                            TextEntry::make('current_location')
                                ->label('Vị trí hiện tại')
                                ->placeholder('—'),
                        ]),
                    ]),

                Section::make('Đặc điểm nhận dạng')
                    ->schema([
                        TextEntry::make('condition_desc')
                            ->label('Mô tả tình trạng')
                            ->placeholder('—'),
                        Grid::make(2)->schema([
                            TextEntry::make('clothes_desc')
                                ->label('Mô tả quần áo')
                                ->placeholder('—'),
                            TextEntry::make('special_marks')
                                ->label('Dấu hiệu đặc biệt')
                                ->placeholder('—'),
                        ]),
                    ]),

                Section::make('Khu vực & bàn giao')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('ward')
                                ->label('Xã/Phường')
                                ->placeholder('—'),
                        ]),
                        TextEntry::make('address_detail')
                            ->label('Địa chỉ chi tiết')
                            ->placeholder('—'),
                        Grid::make(2)->schema([
                            TextEntry::make('handover_agency')
                                ->label('Cơ quan bàn giao')
                                ->placeholder('—'),
                            TextEntry::make('approvedBy.full_name')
                                ->label('Người duyệt')
                                ->placeholder('—'),
                        ]),
                    ]),

                Section::make('Mốc thời gian')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('approved_at')
                                ->label('Ngày duyệt')
                                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i') ?? '—'),
                            TextEntry::make('created_at')
                                ->label('Ngày tạo')
                                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i') ?? '—'),
                            TextEntry::make('updated_at')
                                ->label('Cập nhật gần nhất')
                                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i') ?? '—'),
                        ]),
                        TextEntry::make('ai_processed_at')
                            ->label('AI xử lý lúc')
                            ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i') ?? '—')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
