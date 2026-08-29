<?php

namespace App\Filament\Admin\Resources\Activities\Tables;

use App\Filament\Admin\Support\ActivityDiffBuilder;
use App\Filament\Admin\Support\RecordLinkResolver;
use App\Filament\Admin\Support\Labels;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                        default => 'Khác',
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('causer.full_name')
                    ->label('Người thực hiện')
                    ->default('Hệ thống')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(
                        fn($state, $record) => RecordLinkResolver::label(null, $record->subject_type)
                            . ' #' . ($record->subject_id ?? '')
                    )
                    ->url(fn ($state, $record) => RecordLinkResolver::urlByModel($record->subject_type, $record->subject_id))
                    ->openUrlInNewTab()
                    ->placeholder('—'),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),

                TextColumn::make('log_name')
                    ->label('Loại')
                    ->formatStateUsing(fn ($state) => Labels::logName($state))
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('causer_id')
                    ->label('Người thực hiện')
                    ->options(fn() => User::pluck('full_name', 'id')),

                SelectFilter::make('log_name')
                    ->label('Loại hành động')
                    ->options(fn() => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()),

                Filter::make('created_at')
                    ->label('Khoảng thời gian')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày')->native(false),
                        DatePicker::make('to')->label('Đến ngày')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                Action::make('view_detail')
                    ->label('Xem chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Chi tiết hoạt động')
                    ->modalContent(fn($record) => ActivityDiffBuilder::build($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->toolbarActions([])
            ->striped();
    }
}
