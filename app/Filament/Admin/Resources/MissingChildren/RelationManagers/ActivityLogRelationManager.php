<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Admin\Support\ActivityDiffBuilder;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    protected static ?string $recordTitleAttribute = 'description';

    /**
     * Không có form vì đây là readonly – chỉ xem, không tạo/sửa.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->latest()
            )
            ->columns([
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('Thực hiện bởi')
                    ->default('Hệ thống')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Loại sự kiện')
                    ->options([
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                    ]),

                Filter::make('created_at')
                    ->label('Khoảng thời gian')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Từ ngày')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Từ: ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Đến: ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->toolbarActions([])
            ->recordActions([
                Action::make('view_diff')
                    ->label('Xem thay đổi')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Chi tiết thay đổi – ' . ucfirst((string) $record->event))
                    ->modalContent(fn($record) => ActivityDiffBuilder::build($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}