<?php

namespace App\Filament\Admin\Resources\Clues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                    ->color(fn($state) => match ($state) {
                        'SIGHTING' => 'success',
                        'PHOTO' => 'info',
                        'VIDEO' => 'warning',
                        'RUMOR' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
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
                    ->tooltip(fn($record) => $record->description),
                TextColumn::make('occurred_at')
                    ->label('Thời điểm xảy ra')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                ImageColumn::make('image_path')
                    ->label('Ảnh')
                    ->circular(),
                TextColumn::make('is_verified')
                    ->label('Đã xác minh')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn($state) => $state ? 'Đã xác minh' : 'Chưa xác minh'),
                TextColumn::make('is_anonymous')
                    ->label('Ẩn danh')
                    ->badge()
                    ->color(fn($state) => $state ? 'gray' : 'info')
                    ->formatStateUsing(fn($state) => $state ? 'Ẩn danh' : 'Có danh tính'),
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
                    ->options([
                        '1' => 'Đã xác minh',
                        '0' => 'Chưa xác minh',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}