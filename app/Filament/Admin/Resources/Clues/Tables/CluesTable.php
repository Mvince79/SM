<?php

namespace App\Filament\Admin\Resources\Clues\Tables;

use App\Models\Clue;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CluesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('missingChild.full_name')
                    ->label('Hồ sơ mất tích')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.full_name')
                    ->label('Người gửi')
                    ->placeholder('Ẩn danh')
                    ->searchable(),
                TextColumn::make('clue_type')
                    ->label('Loại manh mối')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'SIGHTING' => 'success',
                        'PHOTO' => 'info',
                        'VIDEO' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        'OTHER' => 'Khác',
                        default => $state,
                    }),
                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(60)
                    ->tooltip(fn (Clue $record): string => $record->description),
                TextColumn::make('location_accuracy_m')
                    ->label('GPS')
                    ->formatStateUsing(fn ($state): string => "±{$state}m")
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('occurred_at')
                    ->label('Thời điểm xảy ra')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                ImageColumn::make('image_path')
                    ->label('Ảnh')
                    ->circular(),
                TextColumn::make('is_verified')
                    ->label('Xác minh')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state): string => $state ? 'Đã xác minh' : 'Chưa xác minh'),
                TextColumn::make('is_hidden')
                    ->label('Hiển thị')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state): string => $state ? 'Đã ẩn' : 'Đang hiện'),
                TextColumn::make('created_at')
                    ->label('Ngày gửi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('clue_type')
                    ->label('Loại manh mối')
                    ->options([
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        'OTHER' => 'Khác',
                    ]),
                SelectFilter::make('is_verified')
                    ->label('Trạng thái xác minh')
                    ->options(['1' => 'Đã xác minh', '0' => 'Chưa xác minh']),
                SelectFilter::make('is_hidden')
                    ->label('Trạng thái hiển thị')
                    ->options(['0' => 'Đang hiện', '1' => 'Đã ẩn']),
                Filter::make('has_gps')
                    ->label('Có GPS')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('lat')->whereNotNull('lng')),
                Filter::make('has_image')
                    ->label('Có ảnh')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('image_path')),
                Filter::make('location_accuracy_m')
                    ->label('Độ chính xác GPS')
                    ->schema([
                        TextInput::make('from')->label('Từ (m)')->numeric()->minValue(0)->maxValue(1000),
                        TextInput::make('to')->label('Đến (m)')->numeric()->minValue(0)->maxValue(1000),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query->where('location_accuracy_m', '>=', (int) $data['from']))
                        ->when(filled($data['to'] ?? null), fn (Builder $query): Builder => $query->where('location_accuracy_m', '<=', (int) $data['to']))),
                Filter::make('gps_shared_at')
                    ->label('Thời gian chia sẻ GPS')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query->whereDate('gps_shared_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query->whereDate('gps_shared_at', '<=', $data['until']))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('verify')
                    ->label('Xác minh')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Clue $record): bool => ! $record->is_verified)
                    ->requiresConfirmation()
                    ->action(function (Clue $record): void {
                        $record->update([
                            'is_verified' => true,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        Cache::forget('badge_clue_unverified');
                    }),
                Action::make('hide')
                    ->label('Ẩn')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (Clue $record): bool => ! $record->is_hidden)
                    ->requiresConfirmation()
                    ->action(fn (Clue $record) => $record->update(['is_hidden' => true])),
                Action::make('show')
                    ->label('Hiện')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (Clue $record): bool => $record->is_hidden)
                    ->action(fn (Clue $record) => $record->update(['is_hidden' => false])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordClasses(fn (Clue $record): ?string => $record->is_hidden ? 'opacity-50' : null)
            ->defaultSort('created_at', 'desc');
    }
}
