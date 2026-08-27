<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use App\Services\UserRoleChangeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ChangeRoleAction
{
    public static function make(): Action
    {
        return Action::make('changeRole')
            ->label('Đổi vai trò')
            ->icon('heroicon-o-user-group')
            ->color('warning')
            ->visible(
                fn (): bool => Auth::check()
                    && Auth::user()->role === 'ADMIN'
            )
            ->fillForm(
                fn (User $record): array => [
                    'role' => $record->role,
                ]
            )
            ->form([
                Select::make('role')
                    ->label('Vai trò mới')
                    ->options([
                        'USER' => 'Người dùng',
                        'VOLUNTEER' => 'Tình nguyện viên',
                        'MODERATOR' => 'Kiểm duyệt viên',
                        'ADMIN' => 'Quản trị viên',
                    ])
                    ->required()
                    ->native(false),
            ])
            ->requiresConfirmation()
            ->modalHeading('Xác nhận đổi vai trò')
            ->modalDescription(
                'Sau khi đổi vai trò, người dùng sẽ bị đăng xuất khỏi tất cả thiết bị. '
                .'Mật khẩu hiện tại của người dùng vẫn được giữ nguyên.'
            )
            ->modalSubmitActionLabel('Xác nhận đổi vai trò')
            ->modalCancelActionLabel('Hủy')
            ->action(function (User $record, array $data): void {
                if ($record->role === $data['role']) {
                    Notification::make()
                        ->title('Vai trò không thay đổi')
                        ->warning()
                        ->send();

                    return;
                }

                $admin = Auth::user();
                abort_unless($admin instanceof User, 403);

                app(UserRoleChangeService::class)->changeRole(
                    $record,
                    $data['role'],
                    $admin,
                );

                Notification::make()
                    ->title('Đã đổi vai trò')
                    ->body('Người dùng đã được gửi thông báo và email hướng dẫn đăng nhập lại.')
                    ->success()
                    ->send();
            });
    }
}
