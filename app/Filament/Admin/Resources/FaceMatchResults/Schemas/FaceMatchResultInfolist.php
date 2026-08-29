<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Schemas;

use App\Filament\Admin\Resources\FoundChildResource;
use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use App\Models\FaceMatchResult;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaceMatchResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Tổng quan ──────────────────────────────────────────────
            Section::make('Tổng quan')
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('missingChild.full_name')
                                ->label('Trẻ mất tích')
                                ->weight('bold')
                                ->getStateUsing(
                                    fn (FaceMatchResult $r): string => ($r->missingChild?->full_name ?? '—')
                                        .($r->missingChild?->case_code
                                            ? ' ('.$r->missingChild->case_code.')'
                                            : '')
                                )
                                ->color('gray')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(
                                    fn ($state, FaceMatchResult $r) => $r->missingChild
                                        ? MissingChildResource::getUrl('view', ['record' => $r->missingChild])
                                        : null
                                )
                                ->openUrlInNewTab()
                                ->tooltip('Xem chi tiết trẻ mất tích'),

                            TextEntry::make('found_id')
                                ->label('Hồ sơ tìm thấy')
                                ->getStateUsing(function (FaceMatchResult $r): string {
                                    if (! $r->foundChild) {
                                        return '#'.$r->found_id;
                                    }

                                    $fc = $r->foundChild;
                                    $parts = ['#'.$fc->id];

                                    if ($fc->province) {
                                        $parts[] = $fc->province->name;
                                    }

                                    if ($fc->found_date) {
                                        $parts[] = 'Tìm thấy '.$fc->found_date->format('d/m/Y');
                                    }

                                    return implode(' · ', $parts);
                                })
                                ->color('info')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(
                                    fn ($state, FaceMatchResult $r) => $r->foundChild
                                        ? FoundChildResource::getUrl('edit', ['record' => $r->foundChild])
                                        : null
                                )
                                ->openUrlInNewTab()
                                ->tooltip('Xem chi tiết hồ sơ tìm thấy'),

                            TextEntry::make('status')
                                ->label('Trạng thái')
                                ->badge()
                                ->icon(fn (string $state): string => match ($state) {
                                    'CONFIRMED' => 'heroicon-o-check-circle',
                                    'DISMISSED' => 'heroicon-o-x-circle',
                                    default => 'heroicon-o-clock',
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'CONFIRMED' => 'success',
                                    'DISMISSED' => 'danger',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'PENDING' => 'Chờ xử lý',
                                    'CONFIRMED' => 'Đã xác nhận',
                                    'DISMISSED' => 'Đã bác bỏ',
                                    default => $state,
                                }),

                            TextEntry::make('created_at')
                                ->label('Tạo lúc')
                                ->since()
                                ->dateTime('d/m/Y H:i')
                                ->tooltip(
                                    fn ($state, FaceMatchResult $r): string => $r->created_at?->format('d/m/Y H:i:s') ?? ''
                                ),
                        ]),
                ]),

            // ── So sánh ảnh ────────────────────────────────────────────
            Section::make('So sánh ảnh')
                ->description('Đặt hai ảnh cạnh nhau để đối chiếu trực quan. Nhấn vào ảnh để phóng to.')
                ->icon('heroicon-o-photo')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            ImageEntry::make('missingPhoto.path_original')
                                ->label('Ảnh trẻ mất tích')
                                ->disk('public')
                                ->height('100%')
                                ->extraImgAttributes([
                                    'style' => 'width:100%;height:300px;object-fit:cover;border-radius:12px;',
                                ])
                                ->openUrlInNewTab(),

                            ImageEntry::make('foundPhoto.path_original')
                                ->label('Ảnh trẻ tìm thấy')
                                ->disk('public')
                                ->height('100%')
                                ->extraImgAttributes([
                                    'style' => 'width:100%;height:300px;object-fit:cover;border-radius:12px;',
                                ])
                                ->openUrlInNewTab(),
                        ]),
                ])
                ->columnSpanFull(),

            // ── Đánh giá AI ────────────────────────────────────────────
            Section::make('Đánh giá AI')
                ->description(
                    'Độ khớp và độ tin cậy do mô hình nhận diện khuôn mặt đưa ra. '.
                    'Ngưỡng: ≥ '.
                    round((float) config('face_match.high_confidence_threshold', 0.85) * 100).
                    '% = khớp mạnh (xanh); '.
                    round((float) config('face_match.score_threshold', 0.70) * 100).
                    '–'.round((float) config('face_match.high_confidence_threshold', 0.85) * 100).
                    '% = cần kiểm tra (vàng); < '.
                    round((float) config('face_match.score_threshold', 0.70) * 100).
                    '% = thấp (đỏ). '.
                    'Nguồn: Realtime = ảnh vừa báo cáo; Age Progression = suy luận theo tuổi; Manual = đối chiếu thủ công.'
                )
                ->icon('heroicon-o-sparkles')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('score')
                                ->label('Độ khớp AI')
                                ->badge()
                                ->size('lg')
                                ->color(
                                    fn ($state): string => $state === null ? 'gray' : self::scoreColor((float) $state)
                                )
                                ->formatStateUsing(
                                    fn ($state): string => number_format((float) $state * 100, 2).'%'
                                ),

                            TextEntry::make('confidence_level')
                                ->label('Độ tin cậy')
                                ->badge()
                                ->color(
                                    fn (?string $state): string => match ($state) {
                                        'HIGH' => 'success',
                                        'MEDIUM' => 'warning',
                                        default => 'gray',
                                    }
                                )
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'HIGH' => 'Khớp mạnh',
                                    'MEDIUM' => 'Cần kiểm tra',
                                    default => 'Thấp',
                                }),

                            TextEntry::make('source')
                                ->label('Nguồn')
                                ->badge()
                                ->color(fn (?string $state): string => match ($state) {
                                    'REALTIME' => 'info',
                                    'AGE_PROGRESSION' => 'warning',
                                    'MANUAL' => 'gray',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'REALTIME' => 'Realtime',
                                    'AGE_PROGRESSION' => 'Age Progression',
                                    'MANUAL' => 'Thủ công',
                                    default => $state ?? '—',
                                }),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextEntry::make('model_used')
                                ->label('Model AI sử dụng')
                                ->placeholder('—')
                                ->color('gray'),

                            TextEntry::make('is_matched')
                                ->label('Kết quả đối chiếu')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state): string => $state ? 'Đã khớp' : 'Chưa khớp'
                                )
                                ->color(
                                    fn ($state): string => $state ? 'success' : 'gray'
                                ),
                        ]),
                ]),

            // ── Quyết định / Lịch sử ───────────────────────────────────
            Section::make('Quyết định')
                ->icon('heroicon-o-clock')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('confirmedBy.full_name')
                                ->label('Xác nhận bởi')
                                ->placeholder('Chưa có'),

                            TextEntry::make('confirmed_at')
                                ->label('Xác nhận lúc')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Chưa xác nhận'),
                        ]),
                ]),
        ]);
    }

    protected static function scoreColor(float $score): string
    {
        if ($score >= (float) config('face_match.high_confidence_threshold', 0.85)) {
            return 'success';
        }
        if ($score >= (float) config('face_match.score_threshold', 0.70)) {
            return 'warning';
        }

        return 'danger';
    }
}
