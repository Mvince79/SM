<?php

namespace App\Filament\Admin\Resources\MissingChildren\Schemas;

use Filament\Infolists\Components\TextEntry;
use App\Models\MissingChild;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\ViewEntry;

class MissingChildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ══════════════════════════════════════════════════════════════
            // HERO PHOTO — Ảnh trẻ được đặt đầu tiên để admin nhận diện ngay
            // ══════════════════════════════════════════════════════════════
            Section::make()
                ->schema([
                    ViewEntry::make('hero_photo')
                        ->hiddenLabel()
                        ->state(function (MissingChild $record): array {
                            $photosCount = $record->photos()->count();

                            return [
                                'url' => $record->detail_primary_photo_url,
                                'other_count' => max(0, $photosCount - 1),
                            ];
                        })
                        ->view('filament.infolists.missing-child-hero-photo')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            // ══════════════════════════════════════════════════════════════
            // BLOCK 1 — Thông tin trẻ (full width)
            // Full width: thông tin quan trọng nhất, admin đọc đầu tiên
            // ══════════════════════════════════════════════════════════════
            Section::make('Thông tin trẻ')
                ->icon('heroicon-o-user')
                ->schema([

                    // Tên (to, bold) + badge Trạng thái (phải)
                    Flex::make([
                        TextEntry::make('full_name')
                            ->label('Họ và tên')
                            // ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),

                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->alignEnd()
                            ->grow(false)
                            ->color(fn($state) => match ($state) {
                                'APPROVED' => 'success',
                                'PENDING' => 'warning',
                                'REJECTED' => 'danger',
                                'FOUND' => 'info',
                                'CLOSED' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'PENDING' => 'Chờ duyệt',
                                'APPROVED' => 'Đã duyệt',
                                'FOUND' => 'Đã tìm thấy',
                                'CLOSED' => 'Đã đóng',
                                'REJECTED' => 'Từ chối',
                                default => $state,
                            }),
                    ])->from('sm'),

                    // Mã hồ sơ — monospace, copyable
                    TextEntry::make('case_code')
                        ->label('Mã hồ sơ')
                        ->fontFamily(FontFamily::Mono)
                        ->color('info')
                        ->copyable()
                        ->copyMessage('Đã sao chép mã hồ sơ!'),

                    // 5 đặc điểm cơ bản ngang hàng
                    Grid::make(5)->schema([
                        TextEntry::make('gender')
                            ->label('Giới tính')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'MALE' => 'Nam',
                                'FEMALE' => 'Nữ',
                                default => 'Không rõ',
                            })
                            ->icon(fn($state) => match ($state) {
                                'MALE' => 'heroicon-o-user',
                                'FEMALE' => 'heroicon-o-user',
                                default => null,
                            })
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'MALE' => 'info',
                                'FEMALE' => 'pink',
                                default => 'gray',
                            }),

                        TextEntry::make('age_estimate')
                            ->label('Tuổi')
                            ->suffix(' tuổi')
                            ->placeholder('—'),

                        TextEntry::make('skin_tone')
                            ->label('Màu da')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'LIGHT' => 'Sáng',
                                'MEDIUM' => 'Trung bình',
                                'DARK' => 'Ngăm',
                                default => '—',
                            })
                            ->placeholder('—'),

                        TextEntry::make('height_cm')
                            ->label('Chiều cao')
                            ->suffix(' cm')
                            ->placeholder('—'),

                        TextEntry::make('weight_kg')
                            ->label('Cân nặng')
                            ->suffix(' kg')
                            ->placeholder('—'),
                    ]),

                    // 3 mô tả nhận dạng ngang hàng
                    Grid::make(3)->schema([
                        TextEntry::make('clothes_desc')
                            ->label('Trang phục khi mất tích')
                            ->placeholder('Chưa có thông tin'),

                        TextEntry::make('special_marks')
                            ->label('Dấu hiệu đặc biệt')
                            ->placeholder('Không có'),

                        TextEntry::make('circumstances')
                            ->label('Hoàn cảnh mất tích')
                            ->placeholder('Chưa có thông tin'),
                    ]),

                ]),

            // ══════════════════════════════════════════════════════════════
            // BLOCK 2 — Liên hệ (sidebar trái) | Địa điểm (phần chính)
            // Flex thay Grid để sidebar thật sự co giãn đúng
            // ══════════════════════════════════════════════════════════════
            Flex::make([

                Section::make('Người liên hệ')
                    ->icon('heroicon-o-phone')
                    ->grow(false)
                    ->schema([
                        TextEntry::make('contact_name')
                            ->label('Tên người liên hệ')
                            ->weight(FontWeight::Medium),

                        TextEntry::make('contact_relation')
                            ->label('Quan hệ với bé')
                            ->formatStateUsing(fn($state) => \App\Models\MissingChild::CONTACT_RELATION_LABELS[$state] ?? $state)
                            ->placeholder('—'),

                        TextEntry::make('contact_phone')
                            ->label('Số điện thoại')
                            ->icon('heroicon-o-phone')
                            ->iconColor('success')
                            ->copyable()
                            ->copyMessage('Đã sao chép SĐT!'),
                    ]),

                Section::make('Địa điểm & thời gian mất tích')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('missing_date')
                                ->label('Ngày mất tích')
                                ->date('d/m/Y'),

                            TextEntry::make('missing_time')
                                ->label('Giờ mất tích')
                                ->placeholder('Không rõ'),

                            TextEntry::make('province.name')
                                ->label('Tỉnh / Thành')
                                ->placeholder('—'),

                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('ward')
                                ->label('Xã / Phường')
                                ->placeholder('—'),

                            TextEntry::make('address_detail')
                                ->label('Địa chỉ chi tiết')
                                ->placeholder('—')
                                ->columnSpan(2),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('lat')
                                ->label('Vĩ độ')
                                ->fontFamily(FontFamily::Mono)
                                ->placeholder('Chưa có GPS'),

                            TextEntry::make('lng')
                                ->label('Kinh độ')
                                ->fontFamily(FontFamily::Mono)
                                ->placeholder('Chưa có GPS'),

                            TextEntry::make('map_link')
                                ->label('Bản đồ')
                                ->state(
                                    fn(MissingChild $record): string =>
                                    $record->lat && $record->lng ? 'Mở Google Maps' : 'Chưa có tọa độ GPS'
                                )
                                ->icon(
                                    fn(MissingChild $record) =>
                                    $record->lat && $record->lng ? 'heroicon-o-map' : 'heroicon-o-x-circle'
                                )
                                ->url(
                                    fn(MissingChild $record): ?string =>
                                    $record->lat && $record->lng
                                        ? "https://www.google.com/maps/search/?api=1&query={$record->lat},{$record->lng}"
                                        : null
                                )
                                ->openUrlInNewTab()
                                ->color(
                                    fn(MissingChild $record) =>
                                    $record->lat && $record->lng ? 'info' : 'gray'
                                ),
                        ]),
                    ]),

            ])->from('md'),

            // ══════════════════════════════════════════════════════════════
            // BLOCK 3 — Trạng thái hồ sơ (sidebar trái) | Người đăng + Hệ thống
            // ══════════════════════════════════════════════════════════════
            Flex::make([

                Section::make('Trạng thái hồ sơ')
                    ->icon('heroicon-o-shield-check')
                    ->grow(false)
                    ->schema([
                        TextEntry::make('approvedBy.full_name')
                            ->label('Duyệt bởi')
                            ->placeholder('Chưa duyệt'),

                        TextEntry::make('approved_at')
                            ->label('Duyệt lúc')
                            ->dateTime('H:i d/m/Y')
                            ->placeholder('—'),

                        TextEntry::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->visible(
                                fn(MissingChild $record) => $record->status === 'REJECTED'
                            )
                            ->color('danger'),

                        TextEntry::make('found_at')
                            ->label('Tìm thấy lúc')
                            ->dateTime('H:i d/m/Y')
                            ->placeholder('—')
                            ->visible(
                                fn(MissingChild $record) => $record->status === 'FOUND'
                            ),
                    ]),

                Section::make('Người đăng & Hệ thống')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('user.full_name')
                                ->label('Người đăng'),

                            TextEntry::make('user_email_display')
                                ->label('Email')
                                ->state(fn(MissingChild $record) => $record->user()->withTrashed()->first()?->email)
                                ->icon('heroicon-o-envelope')
                                ->color('info')
                                ->placeholder('—'),

                            TextEntry::make('created_at')
                                ->label('Ngày đăng')
                                ->dateTime('H:i d/m/Y'),
                        ]),

                        Grid::make(4)->schema([
                            TextEntry::make('view_count')
                                ->label('Lượt xem')
                                ->numeric(),

                            TextEntry::make('share_count')
                                ->label('Lượt chia sẻ')
                                ->numeric(),

                            TextEntry::make('qr_code_url')
                                ->label('QR Code')
                                ->url(fn($state) => $state ?: null)
                                ->formatStateUsing(fn($state) => $state ? 'Tải QR Code' : 'Chưa có')
                                ->icon(fn($state) => $state ? 'heroicon-o-arrow-down-tray' : null)
                                ->openUrlInNewTab()
                                ->color(fn($state) => $state ? 'info' : 'gray'),

                            TextEntry::make('updated_at')
                                ->label('Cập nhật cuối')
                                ->dateTime('H:i d/m/Y'),
                        ]),
                    ]),

            ])->from('md'),

        ]);
    }
}
