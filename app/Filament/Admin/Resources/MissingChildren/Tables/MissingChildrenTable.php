<?php

namespace App\Filament\Admin\Resources\MissingChildren\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MissingChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_code')->label('Mã hồ sơ')->searchable()->sortable()->copyable(),
                TextColumn::make('full_name')->label('Họ tên')->searchable()->sortable(),
                TextColumn::make('age_estimate')->label('Tuổi')->sortable(),
                TextColumn::make('gender')->label('Giới tính')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'MALE' => 'Nam', 'FEMALE' => 'Nữ', default => 'Không rõ',
                    }),
                TextColumn::make('province.name')->label('Tỉnh thành')->sortable(),
                TextColumn::make('missing_date')->label('Ngày mất tích')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'APPROVED' => 'success',
                        'PENDING' => 'warning',
                        'REJECTED' => 'danger',
                        'FOUND' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                        default => $state,
                    }),
                TextColumn::make('created_at')->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                    ]),
                SelectFilter::make('gender')->label('Giới tính')
                    ->options(['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Không rõ']),
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