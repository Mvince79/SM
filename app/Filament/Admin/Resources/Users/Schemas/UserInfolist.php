<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ImageEntry;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->hiddenLabel()
                            ->circular()
                            ->height(120)
                            ->width(120)
                            ->defaultImageUrl(
                                fn(User $record): string =>
                                'https://ui-avatars.com/api/?name='
                                    . urlencode($record->full_name ?: 'User')
                                    . '&size=160&background=e2e8f0&color=64748b'
                            )
                            ->extraImgAttributes([
                                'style' => 'object-fit: cover; border: 3px solid #e5e7eb;',
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Thông tin cơ bản')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('full_name')->label('Họ và tên'),
                            TextEntry::make('email')->label('Email'),
                            TextEntry::make('phone')->label('Số điện thoại')->placeholder('—'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('role')
                                ->label('Vai trò')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'ADMIN' => 'danger',
                                    'MODERATOR' => 'warning',
                                    'USER' => 'info',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'ADMIN' => 'Quản trị viên',
                                    'MODERATOR' => 'Kiểm duyệt viên',
                                    'USER' => 'Người dùng',
                                    default => $state,
                                }),
                            TextEntry::make('status')
                                ->label('Trạng thái')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'ACTIVE' => 'success',
                                    'BANNED' => 'danger',
                                    'PENDING' => 'warning',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'ACTIVE' => 'Hoạt động',
                                    'BANNED' => 'Bị khóa',
                                    'PENDING' => 'Chờ kích hoạt',
                                    default => $state,
                                }),
                            TextEntry::make('email_verified_at')
                                ->label('Xác minh email')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Chưa xác minh'),
                        ]),
                        TextEntry::make('ban_reason')
                            ->label('Lý do khóa')
                            ->placeholder('—')
                            ->visible(fn(User $record): bool => $record->status === 'BANNED'),
                    ]),

                Section::make('Hoạt động')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('last_active_at')
                                ->label('Hoạt động cuối')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('created_at')
                                ->label('Ngày đăng ký')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('deleted_at')
                                ->label('Ngày xóa')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('—')
                                ->visible(fn(User $record): bool => $record->trashed()),
                        ]),
                    ]),

                Section::make('Cài đặt thông báo')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('province.name')->label('Tỉnh thành')->placeholder('—'),
                            TextEntry::make('notify_lat')->label('Vĩ độ')->placeholder('—'),
                            TextEntry::make('notify_lng')->label('Kinh độ')->placeholder('—'),
                        ]),
                        TextEntry::make('notify_radius_km')->label('Bán kính thông báo (km)'),
                    ])
                    ->collapsed(),
            ]);
    }
}
