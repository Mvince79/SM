<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Tables;

use App\Models\FaceMatchResult;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
                Tables\Columns\ImageColumn::make('missingPhoto.path_thumb')
                    ->label('Ảnh mất tích')
                    ->disk('public')
                    ->square()
                    ->size(64),

                Tables\Columns\ImageColumn::make('foundPhoto.path_thumb')
                    ->label('Ảnh tìm thấy')
                    ->disk('public')
                    ->square()
                    ->size(64),

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
                    ->label('Hồ sơ tìm thấy')
                    ->prefix('#')
                    ->fontFamily('mono')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Độ khớp AI')
                    ->formatStateUsing(fn($state): string => number_format((float) $state * 100, 2) . '%')
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        (float) $state >= 0.90 => 'success',
                        (float) $state >= 0.70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Nguồn')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'REALTIME' => 'info',
                        'AGE_PROGRESSION' => 'warning',
                        'MANUAL' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('model_used')
                    ->label('Model AI')
                    ->placeholder('—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'PENDING' => 'Chờ xử lý',
                        'CONFIRMED' => 'Đã xác nhận',
                        'DISMISSED' => 'Đã bác bỏ',
                        default => $state,
                    })
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
                    ->tooltip(fn(FaceMatchResult $record): string => $record->created_at?->format('d/m/Y H:i:s') ?? '')
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

                Tables\Filters\Filter::make('score_range')
                    ->label('Khoảng điểm AI')
                    ->schema([
                        TextInput::make('score_from')
                            ->label('Từ %')
                            ->numeric()
                            ->placeholder('70'),
                        TextInput::make('score_to')
                            ->label('Đến %')
                            ->numeric()
                            ->placeholder('100'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['score_from'] ?? null),
                                fn(Builder $query) => $query->where('score', '>=', ((float) $data['score_from']) / 100)
                            )
                            ->when(
                                filled($data['score_to'] ?? null),
                                fn(Builder $query) => $query->where('score', '<=', ((float) $data['score_to']) / 100)
                            );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->label('Ngày tạo')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Từ ngày'),
                        DatePicker::make('created_until')
                            ->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['created_from'] ?? null),
                                fn(Builder $query) => $query->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                filled($data['created_until'] ?? null),
                                fn(Builder $query) => $query->whereDate('created_at', '<=', $data['created_until'])
                            );
                    }),
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

                        return "Xác nhận kết quả AI ({$score}%) cho hồ sơ {$name}?";
                    })
                    ->modalSubmitActionLabel('Xác nhận')
                    ->action(function (FaceMatchResult $record): void {
                        $record->update([
                            'status' => 'CONFIRMED',
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                        ]);

                        Cache::forget('badge_face_match_pending');
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
                        $record->update([
                            'status' => 'DISMISSED',
                        ]);

                        Cache::forget('badge_face_match_pending');
                    }),

                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
    }
}
