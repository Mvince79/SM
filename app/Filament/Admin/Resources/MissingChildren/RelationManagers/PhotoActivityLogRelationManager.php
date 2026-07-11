<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use App\Models\Photo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class PhotoActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Lịch sử truy cập ảnh';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Activity::query()
                ->where('log_name', 'photo_access')
                ->where('subject_type', Photo::class)
                ->whereIn(
                    'subject_id',
                    $this->getOwnerRecord()
                        ->photos()
                        ->pluck('id')
                )
                ->latest('created_at')
            )
            ->columns([
                TextColumn::make('subject_id')
                    ->label('ID ảnh')
                    ->badge(),

                TextColumn::make('causer.full_name')
                    ->label('Người truy cập')
                    ->placeholder('Không xác định'),

                TextColumn::make('causer.email')
                    ->label('Email')
                    ->placeholder('Không có'),

                TextColumn::make('properties.source')
                    ->label('Nguồn truy cập')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'missing_child_detail' => 'Chi tiết trẻ mất tích',
                        'found_child_detail' => 'Chi tiết trẻ tìm thấy',
                        default => $state ?? 'Không xác định',
                    }),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Chưa có lịch sử truy cập ảnh')
            ->emptyStateDescription('Chưa có người dùng nào truy cập ảnh gốc của hồ sơ này.');
    }
}