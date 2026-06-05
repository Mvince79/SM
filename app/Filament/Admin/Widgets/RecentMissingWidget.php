<?php

namespace App\Filament\Admin\Widgets;

use Filament\Actions\Action;
use App\Mail\MissingChildApproved;
use App\Models\MissingChild;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
                    ->where('status', 'PENDING')
                    ->latest()
                    ->limit(10)
            )->columns([
                    Tables\Columns\TextColumn::make('case_code')
                        ->label('Mã hồ sơ')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('full_name')
                        ->label('Họ tên')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('province.name')
                        ->label('Tỉnh'),

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
                        }),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Ngày tạo')
                        ->dateTime('d/m/Y H:i'),
                ])
            ->recordActions([
                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(MissingChild $record): bool => $record->status === 'PENDING')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (MissingChild $record, array $data): void {
                        $record->update([
                            'status' => 'REJECTED',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
