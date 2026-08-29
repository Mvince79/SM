<?php

namespace App\Filament\Admin\Resources\FcmTokens\Tables;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FcmTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Người dùng')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($state, $record) => $record->user_id
                        ? UserResource::getUrl('view', ['record' => $record->user_id])
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('device_type')
                    ->label('Thiết bị')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ANDROID' => 'Android',
                        'IOS' => 'iOS',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ANDROID' => 'success',
                        'IOS' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('device_name')
                    ->label('Tên thiết bị')
                    ->placeholder('Không xác định')
                    ->searchable(),

                TextColumn::make('token')
                    ->label('Mã token')
                    ->formatStateUsing(fn (?string $state): string => $state ? mb_substr($state, 0, 12) . '…' : '—')
                    ->copyable()
                    ->copyMessage('Đã sao chép token')
                    ->copyMessageDuration(1500),

                TextColumn::make('is_active')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => $state ? 'Đang hoạt động' : 'Không hoạt động')
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Dùng lần cuối')
                    ->since()
                    ->placeholder('Chưa sử dụng')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Đăng ký')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đang hoạt động')
                    ->falseLabel('Không hoạt động'),

                SelectFilter::make('device_type')
                    ->label('Loại thiết bị')
                    ->options([
                        'ANDROID' => 'Android',
                        'IOS' => 'iOS',
                    ]),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->label('Vô hiệu hóa')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Vô hiệu hóa token này?')
                    ->modalDescription('Thiết bị sẽ ngừng nhận thông báo đẩy cho đến khi đăng ký lại.')
                    ->action(fn ($record) => $record->update(['is_active' => 0]))
                    ->visible(fn ($record) => $record->is_active),
            ])
            ->toolbarActions([])
            ->defaultSort('last_used_at', 'desc')
            ->striped();
    }
}
