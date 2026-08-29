<?php

namespace App\Filament\Admin\Resources\KycVerifications\Pages;

use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use App\Models\KycVerification;
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
            // ── Duyệt ──────────────────────────────────────────────────
            Action::make('approve')
                ->label('Duyệt hồ sơ')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận duyệt hồ sơ này?')
                ->modalDescription('Chỉ duyệt khi ảnh giấy tờ rõ ràng, thông tin khớp với lời khai và khuôn mặt tự chụp trùng với ảnh trên CCCD. Người dùng sẽ nhận được thông báo xác thực.')
                ->modalSubmitActionLabel('Đồng ý, duyệt')
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
                    $this->record->user?->notify(new KycApprovedNotification);
                    Cache::forget('badge_kyc_pending');

                    activity('admin')
                        ->causedBy(Auth::id())
                        ->performedOn($this->record)
                        ->withProperties(['user_id' => $this->record->user_id])
                        ->log('Duyệt hồ sơ KYC');

                    Notification::make()
                        ->title('Đã duyệt hồ sơ KYC')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── Từ chối ─────────────────────────────────────────────────
            Action::make('reject')
                ->label('Từ chối')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->form([
                    Textarea::make('rejected_reason')
                        ->label('Lý do từ chối (sẽ gửi đến người dùng)')
                        ->placeholder('Ví dụ: Ảnh CCCD bị mờ, thông tin không khớp với lời khai...')
                        ->required()
                        ->minLength(6),
                ])
                ->requiresConfirmation()
                ->modalHeading('Xác nhận từ chối hồ sơ?')
                ->modalDescription('Người dùng sẽ nhận được lý do để nộp lại. Hãy điền lý do rõ ràng, dễ hiểu.')
                ->modalSubmitActionLabel('Đồng ý, từ chối')
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

                    activity('admin')
                        ->causedBy(Auth::id())
                        ->performedOn($this->record)
                        ->withProperties(['user_id' => $this->record->user_id, 'reason' => $data['rejected_reason']])
                        ->log('Từ chối hồ sơ KYC');

                    Notification::make()
                        ->title('Đã từ chối hồ sơ KYC')
                        ->danger()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── Ca chờ tiếp theo ────────────────────────────────────────
            Action::make('next_pending')
                ->label('Ca chờ tiếp theo')
                ->color('gray')
                ->icon('heroicon-o-arrow-right-circle')
                ->tooltip('Chuyển sang hồ sơ đang chờ duyệt gần nhất')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->action(function () {
                    $next = KycVerification::query()
                        ->where('status', 'PENDING')
                        ->where('id', '!=', $this->record->id)
                        ->orderBy('id')
                        ->first();

                    if (! $next) {
                        Notification::make()
                            ->title('Không còn hồ sơ nào đang chờ duyệt')
                            ->info()
                            ->send();

                        return;
                    }

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $next]));
                }),
        ];
    }
}
