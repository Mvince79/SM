<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FcmTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'fcmTokens';

    protected static ?string $title = 'FCM Tokens';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('device_name')
            ->columns([
                TextColumn::make('device_type')
                    ->label('Thiết bị')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ANDROID' => 'success',
                        'IOS' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('device_name')
                    ->label('Tên thiết bị')
                    ->placeholder('Không xác định')
                    ->searchable(),
                TextColumn::make('token')
                    ->label('Token')
                    ->formatStateUsing(fn (?string $state): string => $state ? mb_substr($state, 0, 8).'...' : '—'),
                TextColumn::make('is_active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => $state ? 'Đang hoạt động' : 'Không hoạt động'),
                TextColumn::make('last_used_at')
                    ->label('Dùng lần cuối')
                    ->since()
                    ->placeholder('Chưa sử dụng')
                    ->sortable(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('last_used_at', 'desc');
    }
}
