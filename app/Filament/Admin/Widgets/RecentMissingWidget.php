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
    protected static ?string $heading = 'Hồ sơ mới nhất';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => MissingChild::query()->latest()->limit(5))
            ->columns([
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
                Action::make('approve')
                    ->label('Approve nhanh')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(MissingChild $record): bool => $record->status === 'PENDING')
                    ->requiresConfirmation()
                    ->action(function (MissingChild $record): void {
                        $record->update([
                            'status' => 'APPROVED',
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);

                        if ($record->user?->email) {
                            Mail::to($record->user->email)
                                ->send(new MissingChildApproved($record));
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
