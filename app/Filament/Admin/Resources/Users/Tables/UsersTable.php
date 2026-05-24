<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'ADMIN' => 'danger',
                        'MODERATOR' => 'warning',
                        'USER' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'ADMIN' => 'Quản trị viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'USER' => 'Người dùng',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'ACTIVE' => 'success',
                        'BANNED' => 'danger',
                        'PENDING' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'ACTIVE' => 'Hoạt động',
                        'BANNED' => 'Bị khóa',
                        'PENDING' => 'Chờ kích hoạt',
                        default => $state,
                    }),
                TextColumn::make('email_verified_at')
                    ->label('Xác minh email')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Chưa xác minh')
                    ->sortable(),
                TextColumn::make('last_active_at')
                    ->label('Hoạt động cuối')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ngày đăng ký')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Ngày xóa')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'ADMIN' => 'Quản trị viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'USER' => 'Người dùng',
                    ]),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'ACTIVE' => 'Hoạt động',
                        'BANNED' => 'Bị khóa',
                        'PENDING' => 'Chờ kích hoạt',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}