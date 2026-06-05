<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CluesRelationManager extends RelationManager
{
    protected static string $relationship = 'clues';

    protected static ?string $title = 'Manh mối';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('clue_type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'SIGHTING' => 'success',
                        'PHOTO' => 'info',
                        'VIDEO' => 'warning',
                        'RUMOR' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        'OTHER' => 'Khác',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->searchable(),

                TextColumn::make('gps_status')
                    ->label('GPS')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->lat && $record->lng ? 'Có GPS' : 'Không có')
                    ->color(fn ($state) => $state === 'Có GPS' ? 'success' : 'gray'),

                TextColumn::make('location_accuracy_m')
                    ->label('Độ chính xác')
                    ->formatStateUsing(fn ($state) => $state ? '±' . $state . 'm' : '—')
                    ->placeholder('—'),

                ImageColumn::make('image_path')
                    ->label('Ảnh')
                    ->circular(),

                TextColumn::make('is_verified')
                    ->label('Xác minh')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ? 'Đã xác minh' : 'Chưa xác minh'),

                TextColumn::make('is_hidden')
                    ->label('Ẩn/Hiện')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Đang ẩn' : 'Đang hiện'),

                TextColumn::make('occurred_at')
                    ->label('Thời điểm')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
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
                    ->label('Xác minh')
                    ->options([
                        '1' => 'Đã xác minh',
                        '0' => 'Chưa xác minh',
                    ]),

                SelectFilter::make('is_hidden')
                    ->label('Ẩn/Hiện')
                    ->options([
                        '1' => 'Đang ẩn',
                        '0' => 'Đang hiện',
                    ]),

                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Từ ngày'),
                        DatePicker::make('until')
                            ->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('verify')
                    ->label('Xác minh')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->is_verified)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_verified' => true,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                    }),

                Action::make('toggle_hidden')
                    ->label(fn ($record) => $record->is_hidden ? 'Hiện' : 'Ẩn')
                    ->icon(fn ($record) => $record->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn ($record) => $record->is_hidden ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_hidden' => ! $record->is_hidden,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordClasses(fn ($record) => $record->is_hidden ? 'opacity-50' : null)
            ->defaultSort('created_at', 'desc');
    }
}