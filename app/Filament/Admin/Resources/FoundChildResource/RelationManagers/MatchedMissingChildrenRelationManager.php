<?php

namespace App\Filament\Admin\Resources\FoundChildResource\RelationManagers;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MatchedMissingChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'faceMatchResults';

    protected static ?string $title = 'Khớp với hồ sơ mất tích';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['missingChild', 'missingPhoto']))
            ->recordTitleAttribute('id')
            ->columns([
                ImageColumn::make('missingPhoto.path_thumb')
                    ->label('Ảnh mất tích')
                    ->disk('public')
                    ->square()
                    ->size(56),

                TextColumn::make('missingChild.full_name')
                    ->label('Trẻ mất tích')
                    ->description(fn ($record): string => $record->missingChild?->case_code ?? '—')
                    ->searchable(
                        query: fn ($query, string $search) => $query->whereHas(
                            'missingChild',
                            fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                                ->orWhere('case_code', 'like', "%{$search}%")
                        )
                    )
                    ->weight('bold'),

                TextColumn::make('score')
                    ->label('Độ khớp AI')
                    ->formatStateUsing(
                        fn ($state): string => number_format((float) $state * 100, 2) . '%'
                    )
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= config('face_match.high_confidence_threshold', 0.85) => 'success',
                        (float) $state >= config('face_match.score_threshold', 0.70) => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('confidence_level')
                    ->label('Độ tin cậy')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'HIGH' => 'Khớp mạnh',
                        'MEDIUM' => 'Cần kiểm tra',
                        default => 'Thấp',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'HIGH' => 'success',
                        'MEDIUM' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PENDING' => 'Chờ xử lý',
                        'CONFIRMED' => 'Đã xác nhận',
                        'DISMISSED' => 'Đã bác bỏ',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'CONFIRMED' => 'success',
                        'DISMISSED' => 'danger',
                        default => 'gray',
                    }),

                // GĐ4 — "Hoàn tác": badge chỉ ra kết quả bị bác bỏ do cascade (xoá/từ
                // chối hồ sơ đối diện) đã tự động revert hồ sơ liên quan về trạng thái
                // trước. Dựa vào found_child_prev_status / missing_child_prev_status
                // (chỉ có khi revert qua cascade, khác với bác bỏ thủ công).
                TextColumn::make('revert')
                    ->label('Hoàn tác')
                    ->badge()
                    ->color(fn ($state, $record): string =>
                        $record->isRevertedViaCascade() ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state, $record): string =>
                        $record->isRevertedViaCascade() ? 'Đã hoàn tác' : '')
                    ->description(fn ($record): string =>
                        (string) ($record->revert_summary ?? '')),

                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('score', 'desc')
            ->recordActions([
                Action::make('view_missing')
                    ->label('Xem hồ sơ mất tích')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn (Model $record): bool => (bool) $record->missingChild)
                    ->url(
                        fn (Model $record): ?string => $record->missingChild
                            ? MissingChildResource::getUrl('view', ['record' => $record->missingChild])
                            : null
                    )
                    ->openUrlInNewTab(),
            ]);
    }
}
