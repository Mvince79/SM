<?php

namespace App\Filament\Admin\Resources\Activities\Tables;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('causer.full_name')
                    ->label('Người thực hiện')
                    ->default('Hệ thống'),
                TextColumn::make('log_name')
                    ->label('Loại hành động')
                    ->badge(),
                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn($state) => $state ? class_basename($state) : '—'),
                TextColumn::make('description')
                    ->label('Mô tả'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('causer_id')
                    ->label('Người thực hiện')
                    ->options(fn() => User::pluck('full_name', 'id')),
                SelectFilter::make('log_name')
                    ->label('Loại hành động')
                    ->options(fn() => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}