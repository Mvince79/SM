<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;
    use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
    use App\Filament\Admin\Resources\MissingChildren\Widgets\MissingChildStatsWidget;
    use App\Models\HandoverPoint;
    use App\Services\MissingChildCascadeService;
    use Filament\Actions\Action;
    use Filament\Actions\EditAction;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\Textarea;
    use Filament\Notifications\Notification;
    use Filament\Resources\Pages\ViewRecord;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\DB;

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
                        // Cascade revert (quyết định #1) chung 1 transaction: nếu hồ sơ
                        // mất tích này từng có kết quả đối chiếu CONFIRMED (đẩy FoundChild
                        // sang MATCHED), trả FoundChild về trạng thái trước và bác bỏ kết
                        // quả đối chiếu, tránh "Đã tìm thấy" giả. Không đụng thông báo Từ
                        // chối (do MissingChildObserver phát riêng).
                        DB::transaction(function () use ($data) {
                            $this->record->update([
                                'status' => 'REJECTED',
                                'rejection_reason' => $data['rejection_reason'],
                            ]);

                            app(MissingChildCascadeService::class)->handleRemoval($this->record, includePending: false);
                        });
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
                    ->modalDescription('Hồ sơ sẽ chuyển sang trạng thái "Đã tìm thấy". Gia đình sẽ nhận thông báo qua chuông và email.')
                    ->action(function () {
                        $this->record->update([
                            'status' => 'FOUND',
                            'found_at' => now(),
                        ]);
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã đánh dấu tìm thấy')->success()->send();
                        $this->refreshFormData(['status', 'found_at']);
                    }),

                // H-01/H-04 — Đánh dấu "đã bàn giao" cho cơ quan chức năng.
                // Mở khóa toàn bộ Nhóm H: card "Việc cần làm" cho gia đình, job nhắc 3/7
                // ngày, và trang "Xác nhận hộ". Hệ thống chỉ ghi nhận, không thay thế
                // cơ quan chức năng (bàn giao thật diễn ra ngoài hệ thống).
                Action::make('mark_handed_over')
                    ->label('Đánh dấu đã bàn giao')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->visible(fn() => in_array($this->record->status, ['APPROVED', 'FOUND'], true))
                    ->form([
                        Select::make('handover_point_id')
                            ->label('Điểm tiếp nhận / bàn giao')
                            ->options(fn() => HandoverPoint::active()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->nullable()
                            ->helperText('Nơi trẻ đang được giữ (nếu biết). Gia đình sẽ thấy địa chỉ & hotline này trong mục "Việc cần làm".'),
                        Textarea::make('reason')
                            ->label('Lý do xác nhận bàn giao (bắt buộc, tối thiểu 20 ký tự)')
                            ->required()
                            ->minLength(20)
                            ->rows(3)
                            ->helperText('Ví dụ: "Đã gọi điện xác minh qua gia đình, công an phường X xác nhận đã bàn giao bé vào ngày...".'),
                    ])
                    ->modalHeading('Đánh dấu đã bàn giao cho cơ quan chức năng?')
                    ->modalDescription('Hồ sơ chuyển sang trạng thái "Đã bàn giao". Gia đình sẽ nhận hướng dẫn để đến nhận lại con. Hệ thống chỉ ghi nhận thông tin, không thay thế cơ quan chức năng.')
                    ->modalSubmitActionLabel('Đánh dấu bàn giao')
                    ->action(function (array $data) {
                        // Dùng confirmHandoverByAdmin: validate lý do >= 20 ký tự TẠI ĐÂY
                        // (trước save) thay vì update() thô — StatusChangeAuditObserver chỉ
                        // chạy ở event `updated` (sau ghi DB) nên không rollback được.
                        $this->record->confirmHandoverByAdmin(
                            $data['reason'],
                            Auth::id(),
                            $data['handover_point_id'] ?? null
                        );
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã đánh dấu bàn giao')->success()->send();
                        $this->refreshFormData(['status', 'handover_point_id', 'handover_confirmed_at', 'handover_confirmed_by']);
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

                // H-04 — Mở trang xác nhận đoàn tụ hộ (chỉ hồ sơ đã bàn giao).
                Action::make('confirm_reunion_manually')
                    ->label('Ghi nhận xác nhận của gia đình')
                    ->icon('heroicon-o-user-group')
                    ->color('success')
                    ->visible(fn() => $this->record->status === \App\Models\MissingChild::STATUS_HANDED_OVER)
                    ->url(fn() => MissingChildResource::getUrl('confirm-reunion', ['record' => $this->record])),

                EditAction::make()->label('Chỉnh sửa'),
            ];
        }
    }