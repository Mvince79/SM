<?php

namespace App\Filament\Admin\Resources\SupportTickets\Pages;

use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Quick Action: nhận xử lý ticket ngay (gán admin hiện tại) ──
            Action::make('assign_to_me')
                ->label('Nhận xử lý')
                ->icon('heroicon-o-hand-raised')
                ->color('info')
                ->visible(fn(): bool => is_null($this->record->assigned_admin_id))
                ->action(function () {
                    $this->record->update([
                        'assigned_admin_id' => Auth::id(),
                        'status' => 'IN_PROGRESS',
                    ]);
                    Notification::make()->title('Đã nhận xử lý ticket')->success()->send();
                    $this->refreshFormData(['assigned_admin_id', 'status']);
                }),

            // ── Quick Action: chuyển admin phụ trách khác ──
            Action::make('reassign')
                ->label('Chuyển phụ trách')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Select::make('assigned_admin_id')
                        ->label('Admin phụ trách mới')
                        ->options(
                            User::query()
                                ->where('role', 'ADMIN')
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                        )
                        ->searchable()
                        ->required(),
                ])
                ->fillForm(fn(): array => ['assigned_admin_id' => $this->record->assigned_admin_id])
                ->action(function (array $data) {
                    $this->record->update(['assigned_admin_id' => $data['assigned_admin_id']]);
                    Notification::make()->title('Đã chuyển admin phụ trách')->success()->send();
                    $this->refreshFormData(['assigned_admin_id']);
                }),

            // ── Quick Action: đổi trạng thái nhanh, không cần vào Edit ──
            Action::make('mark_resolved')
                ->label('Đánh dấu đã giải quyết')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => in_array($this->record->status, ['OPEN', 'IN_PROGRESS']))
                ->requiresConfirmation()
                ->modalHeading('Đánh dấu ticket đã giải quyết?')
                ->action(function () {
                    $this->record->update(['status' => 'RESOLVED']);
                    Notification::make()->title('Đã đánh dấu giải quyết')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('close_ticket')
                ->label('Đóng ticket')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn(): bool => $this->record->status !== 'CLOSED')
                ->requiresConfirmation()
                ->modalHeading('Đóng ticket này?')
                ->action(function () {
                    $this->record->update(['status' => 'CLOSED']);
                    Notification::make()->title('Đã đóng ticket')->send();
                    $this->refreshFormData(['status']);
                }),

            EditAction::make()->label('Chỉnh sửa'),
        ];
    }
}
