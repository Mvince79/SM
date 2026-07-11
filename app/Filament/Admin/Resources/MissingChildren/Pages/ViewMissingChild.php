<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;
    use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
    use App\Filament\Admin\Resources\MissingChildren\Widgets\MissingChildStatsWidget;
    use Filament\Actions\Action;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Textarea;
    use Filament\Notifications\Notification;
    use Filament\Resources\Pages\ViewRecord;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Cache;

    class ViewMissingChild extends ViewRecord
    {
        protected static string $resource = MissingChildResource::class;

        protected function getHeaderWidgets(): array
        {
            return [MissingChildStatsWidget::class];
        }

        protected function getHeaderWidgetsData(): array
        {
            return ['record' => $this->record];
        }

        protected function getHeaderActions(): array
        {
            return [

                Action::make('approve')
                    ->label('Duyệt hồ sơ')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn() => $this->record->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt hồ sơ này?')
                    ->modalDescription('Hồ sơ sẽ hiển thị công khai. Người đăng nhận email thông báo.')
                    ->modalSubmitActionLabel('Duyệt ngay')
                    ->action(function () {
                        $this->record->update([
                            'status' => 'APPROVED',
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),  // dùng facade tránh IDE warning
                        ]);
                        Cache::forget('badge_missing_pending');
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã duyệt hồ sơ')->success()->send();
                        $this->refreshFormData(['status', 'approved_at', 'approved_by']);
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn() => $this->record->status === 'PENDING')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->placeholder('Nhập lý do để thông báo cho người đăng...')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'status' => 'REJECTED',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Cache::forget('badge_missing_pending');
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã từ chối hồ sơ')->warning()->send();
                        $this->refreshFormData(['status', 'rejection_reason']);
                    }),

                Action::make('mark_found')
                    ->label('Đã tìm thấy')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn() => $this->record->status === 'APPROVED')
                    ->requiresConfirmation()
                    ->modalHeading('Đánh dấu đã tìm thấy?')
                    ->modalDescription('Hồ sơ sẽ chuyển sang trạng thái "Đã tìm thấy". Người đăng sẽ nhận thông báo.')
                    ->action(function () {
                        $this->record->update([
                            'status' => 'FOUND',
                            'found_at' => now(),
                        ]);
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã đánh dấu tìm thấy')->success()->send();
                        $this->refreshFormData(['status', 'found_at']);
                    }),

                Action::make('close')
                    ->label('Đóng hồ sơ')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn() => in_array($this->record->status, ['APPROVED', 'FOUND']))
                    ->requiresConfirmation()
                    ->modalHeading('Đóng hồ sơ này?')
                    ->modalDescription('Hồ sơ sẽ không còn hiển thị công khai.')
                    ->action(function () {
                        $this->record->update(['status' => 'CLOSED']);
                        Notification::make()->title('Đã đóng hồ sơ')->send();
                        $this->refreshFormData(['status']);
                    }),

                EditAction::make()->label('Chỉnh sửa'),
            ];
        }
    }