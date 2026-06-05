<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Tables;

use App\Models\FaceMatchResult;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FaceMatchResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('missingChild.full_name')
                    ->label('Trẻ mất tích')
                    ->description(fn(FaceMatchResult $record): string => $record->missingChild?->case_code ?? '—')
                    ->searchable(query: fn(Builder $query, string $search) => $query->whereHas(
                        'missingChild',
                        fn($q) => $q->where('full_name', 'like', "%{$search}%")
                            ->orWhere('case_code', 'like', "%{$search}%")
                    ))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('found_id')
                    ->label('Hồ sơ trẻ tìm thấy')
                    ->prefix('#')
                    ->fontFamily('mono')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Độ khớp AI')
                    ->formatStateUsing(fn(float $state): string => number_format($state * 100, 2) . '%')
                    ->badge()
                    ->color(fn(float $state): string => match (true) {
                        $state >= 0.90 => 'success',
                        $state >= 0.70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Nguồn')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'REALTIME' => 'info',
                        'AGE_PROGRESSION' => 'warning',
                        'MANUAL' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('model_used')
                    ->label('Model AI')
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'CONFIRMED' => 'success',
                        'DISMISSED' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('confirmedBy.full_name')
                    ->label('Xác nhận bởi')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Xác nhận lúc')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->tooltip(fn($record) => $record->created_at->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ xử lý',
                        'CONFIRMED' => 'Đã xác nhận',
                        'DISMISSED' => 'Đã bác bỏ',
                    ]),

                SelectFilter::make('source')
                    ->label('Nguồn')
                    ->options([
                        'REALTIME' => 'Realtime',
                        'AGE_PROGRESSION' => 'Age Progression',
                        'MANUAL' => 'Manual',
                    ]),

                Tables\Filters\Filter::make('high_confidence')
                    ->label('Độ khớp ≥ 70%')
                    ->query(fn(Builder $query) => $query->where('score', '>=', 0.70))
                    ->toggle(),

                Tables\Filters\Filter::make('very_high_confidence')
                    ->label('Độ khớp ≥ 90%')
                    ->query(fn(Builder $query) => $query->where('score', '>=', 0.90))
                    ->toggle(),
            ])

            ->recordActions([
                Action::make('confirm')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn(FaceMatchResult $record): bool => $record->status !== 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận kết quả AI match')
                    ->modalDescription(function (FaceMatchResult $record): string {
                        $score = number_format($record->score * 100, 2);
                        $name = $record->missingChild?->full_name ?? '?';
                        return "Xác nhận kết quả AI ({$score}%) cho hồ sơ {$name}? Hành động này sẽ được ghi nhận.";
                    })
                    ->modalSubmitActionLabel('Xác nhận')
                    ->action(function (FaceMatchResult $record): void {
                        $record->update([
                            'status' => 'CONFIRMED',
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                        ]);
                        Cache::forget('badge_facematch_pending');
                    }),

                Action::make('dismiss')
                    ->label('Bác bỏ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->hidden(fn(FaceMatchResult $record): bool => $record->status !== 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Bác bỏ kết quả AI match')
                    ->modalDescription('Kết quả AI này sẽ được đánh dấu là không chính xác.')
                    ->modalSubmitActionLabel('Bác bỏ')
                    ->action(function (FaceMatchResult $record): void {
                        $record->update(['status' => 'DISMISSED']);
                        Cache::forget('badge_facematch_pending');
                    }),

                ViewAction::make(),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
    }
}