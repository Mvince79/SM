<?php

namespace App\Filament\Admin\Widgets;

use App\Events\PostStatusChanged;
use App\Models\MissingChild;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class RecentMissingWidget extends TableWidget
{
    protected static ?string $heading = 'Hồ sơ đang chờ duyệt';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn(): Builder => MissingChild::query()
                    ->with(['province:id,name'])
                    ->where('status', 'PENDING')
                    ->latest()
                    ->limit(10)
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('case_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Họ tên')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('province.name')
                    ->label('Tỉnh / Thành')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'FOUND' => 'primary',
                        'CLOSED' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'REJECTED' => 'Từ chối',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->tooltip(fn($record) => $record->created_at->format('d/m/Y H:i:s')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(MissingChild $record): bool => $record->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận duyệt hồ sơ')
                    ->modalDescription(fn(MissingChild $record) => "Duyệt hồ sơ {$record->case_code} — {$record->full_name}?")
                    ->action(function (MissingChild $record): void {
                        // status/approved_at/approved_by bị guard → forceFill thay update()
                        $record->forceFill([
                            'status' => MissingChild::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ])->save();
                        Cache::forget('dashboard_stats_overview');
                        Cache::forget('badge_missing_pending');
                        PostStatusChanged::dispatch($record->refresh());
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(MissingChild $record): bool => $record->status === 'PENDING')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->minLength(10)
                            ->rows(3)
                            ->placeholder('Nhập lý do từ chối (tối thiểu 10 ký tự)...'),
                    ])
                    ->action(function (MissingChild $record, array $data): void {
                        $record->forceFill([
                            'status' => MissingChild::STATUS_REJECTED,
                            'rejection_reason' => $data['rejection_reason'],
                        ])->save();
                        Cache::forget('dashboard_stats_overview');
                        Cache::forget('badge_missing_pending');
                        PostStatusChanged::dispatch($record->refresh());
                    }),
            ]);
    }
}
