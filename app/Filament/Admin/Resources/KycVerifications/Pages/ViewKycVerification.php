<?php

namespace App\Filament\Admin\Resources\KycVerifications\Pages;

use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ViewKycVerification extends ViewRecord
{
    protected static string $resource = KycVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Duyệt')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn() => $this->record->status === 'PENDING')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'VERIFIED',
                        'reviewed_by' => Auth::id(),
                        'verified_at' => now(),
                        'rejected_reason' => null,
                    ]);

                    $this->record->user?->update([
                        'kyc_status' => 'VERIFIED',
                    ]);
                    $this->record->user?->notify(new KycApprovedNotification());
                    Cache::forget('badge_kyc_pending');

                    Notification::make()
                        ->title('Đã duyệt KYC')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('reject')
                ->label('Từ chối')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn() => $this->record->status === 'PENDING')
                ->form([
                    Textarea::make('rejected_reason')
                        ->label('Lý do từ chối')
                        ->required()
                        ->minLength(6),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'REJECTED',
                        'reviewed_by' => Auth::id(),
                        'verified_at' => null,
                        'rejected_reason' => $data['rejected_reason'],
                    ]);

                    $this->record->user?->update([
                        'kyc_status' => 'REJECTED',
                    ]);
                    $this->record->user?->notify(
                        new KycRejectedNotification($data['rejected_reason'])
                    );
                    Cache::forget('badge_kyc_pending');

                    Notification::make()
                        ->title('Đã từ chối KYC')
                        ->danger()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
