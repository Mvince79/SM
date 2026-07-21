<?php

namespace App\Filament\Admin\Widgets;

use App\Models\EscalationQueue;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EscalationQueueWidget extends TableWidget
{
    protected static ?string $heading = 'Cần hỗ trợ khẩn cấp';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => EscalationQueue::query()
                    ->where('status', EscalationQueue::STATUS_PENDING)
                    ->with([
                        'notificationRecipient.user',
                        'notificationRecipient.event',
                    ])
                    ->orderBy('due_at')
            )
            ->poll('30s')
            ->emptyStateHeading('Không có trường hợp khẩn cấp')
            ->emptyStateDescription(
                'Hiện tại không có thông báo quan trọng nào đang chờ hỗ trợ.'
            )
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('notificationRecipient.rendered_title')
                    ->label('Thông báo')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('notificationRecipient.event.event_type')
                    ->label('Loại sự kiện')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'FACE_MATCH_CONFIRMED' => 'Khớp khuôn mặt',
                            'CASE_APPROVED' => 'Tin đã được duyệt',
                            'CLUE_VERIFIED' => 'Manh mối đã xác minh',
                            'HANDOVER_READY' => 'Sẵn sàng bàn giao',
                            default => $state ?? 'Không xác định',
                        }
                    )
                    ->color('danger'),

                TextColumn::make('notificationRecipient.user.full_name')
                    ->label('Gia đình')
                    ->placeholder('Không xác định'),

                TextColumn::make('notificationRecipient.user.phone')
                    ->label('Số điện thoại')
                    ->placeholder('Chưa cập nhật')
                    ->copyable()
                    ->copyMessage('Đã sao chép số điện thoại')
                    ->weight('bold'),

                TextColumn::make('due_at')
                    ->label('Quá hạn từ')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('callFamily')
                    ->label('Gọi ngay gia đình')
                    ->icon('heroicon-o-phone')
                    ->color('danger')
                    ->url(function (EscalationQueue $record): ?string {
                        $phone = $record
                            ->notificationRecipient
                            ?->user
                            ?->phone;

                        if (blank($phone)) {
                            return null;
                        }

                        $normalizedPhone = preg_replace('/\s+/', '', $phone);

                        return 'tel:'.$normalizedPhone;
                    })
                    ->disabled(
                        fn (EscalationQueue $record): bool => blank(
                            $record->notificationRecipient?->user?->phone
                        )
                    ),

                Action::make('resolve')
                    ->label('Đã gọi xong')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận đã hỗ trợ gia đình')
                    ->modalDescription(function (EscalationQueue $record): string {
                        $name = $record
                            ->notificationRecipient
                            ?->user
                            ?->full_name ?? 'người dùng';

                        $phone = $record
                            ->notificationRecipient
                            ?->user
                            ?->phone ?? 'chưa cập nhật';

                        return "Xác nhận Admin đã gọi cho {$name} qua số {$phone}. Trường hợp này sẽ được đánh dấu RESOLVED.";
                    })
                    ->modalSubmitActionLabel('Xác nhận đã gọi')
                    ->action(function (EscalationQueue $record): void {
                        $record->update([
                            'status' => EscalationQueue::STATUS_RESOLVED,
                            'resolved_by_admin_id' => Auth::id(),
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Đã đánh dấu trường hợp là RESOLVED')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}