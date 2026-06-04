<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\SelectAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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
                    ->color(fn ($state) => match ($state) {
                        'ADMIN' => 'danger',
                        'MODERATOR' => 'warning',
                        'USER' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ADMIN' => 'Quản trị viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'USER' => 'Người dùng',
                        default => $state,
                    }),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ACTIVE' => 'success',
                        'BANNED' => 'danger',
                        'PENDING' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
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

                SelectFilter::make('province_id')
                    ->label('Tỉnh/Thành')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('email_verified')
                    ->label('Đã xác minh email')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),

                Filter::make('google_login')
                    ->label('Đăng nhập Google')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('google_id')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('ban')
                    ->label('Khóa')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->status !== 'BANNED')
                    ->form([
                        Textarea::make('ban_reason')
                            ->label('Lý do khóa')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'status' => 'BANNED',
                            'ban_reason' => $data['ban_reason'],
                        ]);
                    }),

                Action::make('unban')
                    ->label('Mở khóa')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === 'BANNED')
                    ->action(function (User $record): void {
                        $record->update([
                            'status' => 'ACTIVE',
                            'ban_reason' => null,
                        ]);
                    }),

                SelectAction::make('change_role')
                    ->label('Đổi quyền')
                    ->options([
                        'USER' => 'Người dùng',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'ADMIN' => 'Quản trị viên',
                    ])
                    ->visible(fn (): bool => Auth::check() && Auth::user()->role === 'ADMIN')
                    ->action(function (User $record, string $state): void {
                        $record->update([
                            'role' => $state,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('ban')
                        ->label('Khóa tài khoản')
                        ->color('danger')
                        ->form([
                            Textarea::make('ban_reason')
                                ->label('Lý do khóa')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update([
                                'status' => 'BANNED',
                                'ban_reason' => $data['ban_reason'],
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unban')
                        ->label('Mở khóa tài khoản')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update([
                                'status' => 'ACTIVE',
                                'ban_reason' => null,
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
