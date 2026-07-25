<?php

namespace App\Filament\Admin\Resources\SupportTickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Mã')
                    ->sortable(),

                TextColumn::make('user.full_name')
                    ->label('Người gửi')
                    ->searchable()
                    ->placeholder('Không xác định'),

                TextColumn::make('category')
                    ->label('Danh mục')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'BUG' => 'Báo lỗi',
                        'COMPLAINT' => 'Khiếu nại',
                        'FEEDBACK' => 'Góp ý',
                        'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'BUG' => 'danger',
                        'COMPLAINT' => 'warning',
                        'FEEDBACK' => 'info',
                        'FRAUD_SUSPECT' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('subject')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('priority')
                    ->label('Ưu tiên')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'LOW' => 'Thấp',
                        'MEDIUM' => 'Trung bình',
                        'HIGH' => 'Cao',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'LOW' => 'gray',
                        'MEDIUM' => 'warning',
                        'HIGH' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'OPEN' => 'Đang mở',
                        'IN_PROGRESS' => 'Đang xử lý',
                        'RESOLVED' => 'Đã giải quyết',
                        'CLOSED' => 'Đã đóng',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'IN_PROGRESS' => 'info',
                        'RESOLVED' => 'success',
                        'CLOSED' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('assignedAdmin.full_name')
                    ->label('Admin phụ trách')
                    ->searchable()
                    ->placeholder('Chưa phân công'),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->label('Mức độ ưu tiên')
                    ->options([
                        'LOW' => 'Thấp',
                        'MEDIUM' => 'Trung bình',
                        'HIGH' => 'Cao',
                    ]),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'OPEN' => 'Đang mở',
                        'IN_PROGRESS' => 'Đang xử lý',
                        'RESOLVED' => 'Đã giải quyết',
                        'CLOSED' => 'Đã đóng',
                    ]),

                SelectFilter::make('category')
                    ->label('Danh mục')
                    ->options([
                        'BUG' => 'Báo lỗi',
                        'COMPLAINT' => 'Khiếu nại',
                        'FEEDBACK' => 'Góp ý',
                        'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo',
                    ]),

                SelectFilter::make('assigned_admin_id')
                    ->label('Admin phụ trách')
                    ->relationship('assignedAdmin', 'full_name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Xử lý'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}