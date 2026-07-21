<?php

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
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
                fn (): bool =>
                    Auth::check()
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
                        'THIRD_PARTY_VERIFIER' => 'Bên thứ ba xác minh',
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
                . 'Mật khẩu hiện tại của người dùng vẫn được giữ nguyên.'
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

                /*
                 * TODO I-06:
                 * Thay đoạn update bên dưới bằng UserRoleChangeService
                 * sau khi task I-06 được merge.
                 */
                $record->update([
                    'role' => $data['role'],
                ]);

                Notification::make()
                    ->title('Đã đổi vai trò')
                    ->body('Vai trò của người dùng đã được cập nhật.')
                    ->success()
                    ->send();
            });
    }
}