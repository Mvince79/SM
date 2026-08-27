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
use Filament\Forms\Components\Textarea;
use App\Filament\Admin\Resources\Users\Actions\ChangeRoleAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
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
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->placeholder('—')
                    ->visibleFrom('lg'),

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
                    ->sortable()
                    ->visibleFrom('xl'),

                TextColumn::make('last_active_at')
                    ->label('Hoạt động cuối')
                    ->date('d/m/Y')
                    ->description(fn(User $record): ?string => $record->last_active_at?->format('H:i'))
                    ->placeholder('—')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'ADMIN' => 'Quản trị viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'VOLUNTEER' => 'Tình nguyện viên',
                        'USER' => 'Người dùng',
                        default => $state ?? 'Không xác định',
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'ADMIN' => 'danger',
                        'MODERATOR' => 'warning',
                        'VOLUNTEER' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('completed_volunteer_assignments_count')
                    ->label('Nhiệm vụ hoàn thành')
                    ->counts([
                        'volunteerAssignments' => fn(Builder $query): Builder =>
                        $query->whereIn('status', [
                            'VERIFIED',
                            'COULD_NOT_VERIFY',
                        ]),
                    ])
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->alignCenter()
                    ->visibleFrom('lg'),

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
                TrashedFilter::make()
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('role')
                    ->label('Vai trò')
                    ->options([
                        'ADMIN' => 'Quản trị viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'VOLUNTEER' => 'Tình nguyện viên',
                        'USER' => 'Người dùng',
                    ])
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'ACTIVE' => 'Hoạt động',
                        'BANNED' => 'Bị khóa',
                        'PENDING' => 'Chờ kích hoạt',
                    ])
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('province_id')
                    ->label('Tỉnh/Thành')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->columnSpan(1),

                Filter::make('email_verified')
                    ->label('Đã xác minh email')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->columnSpan(1),

                Filter::make('google_login')
                    ->label('Đăng nhập Google')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('google_id'))
                    ->columnSpan(1),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 2,
                'lg' => 2,
                'xl' => 2,
            ])
            ->filtersTriggerAction(
                fn(Action $action): Action => $action
                    ->button()
                    ->label('Bộ lọc')
                    ->icon('heroicon-o-funnel')
            )
            ->recordActions([
                EditAction::make(),
                ChangeRoleAction::make(),

                Action::make('ban')
                    ->label('Khóa')
                    ->color('danger')
                    ->visible(fn(User $record): bool => $record->status !== 'BANNED')
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
                    ->visible(fn(User $record): bool => $record->status === 'BANNED')
                    ->action(function (User $record): void {
                        $record->update([
                            'status' => 'ACTIVE',
                            'ban_reason' => null,
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
