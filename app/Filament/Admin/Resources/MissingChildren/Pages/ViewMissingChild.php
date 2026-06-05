<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use App\Filament\Admin\Widgets\MissingChildStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMissingChild extends ViewRecord
{
    protected static string $resource = MissingChildResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MissingChildStatsWidget::class,
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'record' => $this->record,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Duyệt')
                ->color('success')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'APPROVED',
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                    ]);

                    $this->refreshFormData([
                        'status',
                        'approved_at',
                        'approved_by',
                    ]);

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã duyệt hồ sơ.',
                    ]);
                }),

            Action::make('reject')
                ->label('Từ chối')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Lý do từ chối')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'REJECTED',
                        'rejection_reason' => $data['rejection_reason'],
                    ]);

                    $this->refreshFormData([
                        'status',
                        'rejection_reason',
                    ]);

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã từ chối hồ sơ.',
                    ]);
                }),

            Action::make('mark_found')
                ->label('Đã tìm thấy')
                ->color('info')
                ->visible(fn () => $this->record->status === 'APPROVED')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'FOUND',
                        'found_at' => now(),
                    ]);

                    $this->refreshFormData([
                        'status',
                        'found_at',
                    ]);

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã cập nhật trạng thái tìm thấy.',
                    ]);
                }),

            Action::make('close')
                ->label('Đóng hồ sơ')
                ->color('gray')
                ->visible(fn () => $this->record->status !== 'CLOSED')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'CLOSED',
                    ]);

                    $this->refreshFormData([
                        'status',
                    ]);

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã đóng hồ sơ.',
                    ]);
                }),

            EditAction::make(),
        ];
    }
}