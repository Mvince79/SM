<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Schemas;

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
            Section::make('Ảnh AI gợi ý')
                ->schema([
                    Grid::make(2)->schema([
                        ImageEntry::make('missingPhoto.path_original')
                            ->label('Ảnh trẻ mất tích')
                            ->disk('public')
                            ->height(260)
                            ->width('100%'),

                        ImageEntry::make('foundPhoto.path_original')
                            ->label('Ảnh trẻ tìm thấy')
                            ->disk('public')
                            ->height(260)
                            ->width('100%'),
                    ]),
                ]),

            Section::make('Thông tin AI Match')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('missingChild.full_name')
                            ->label('Trẻ mất tích'),

                        TextEntry::make('missingChild.case_code')
                            ->label('Mã hồ sơ'),

                        TextEntry::make('found_id')
                            ->label('Hồ sơ tìm thấy')
                            ->formatStateUsing(fn ($state) => '#' . $state),

                        TextEntry::make('score')
                            ->label('Độ khớp AI')
                            ->badge()
                            ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2) . '%')
                            ->color(fn ($state) => match (true) {
                                (float) $state >= config('face_match.high_confidence_threshold', 0.85) => 'success',
                                (float) $state >= config('face_match.score_threshold', 0.70) => 'warning',
                                default => 'danger',
                            }),

                        TextEntry::make('confidence_level')
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
                            }),

                        TextEntry::make('source')
                            ->label('Nguồn')
                            ->badge(),

                        TextEntry::make('model_used')
                            ->label('Model AI')
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'PENDING' => 'Chờ xử lý',
                                'CONFIRMED' => 'Đã xác nhận',
                                'DISMISSED' => 'Đã bác bỏ',
                                default => $state,
                            })
                            ->color(fn ($state) => match ($state) {
                                'PENDING' => 'warning',
                                'CONFIRMED' => 'success',
                                'DISMISSED' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('confirmedBy.full_name')
                            ->label('Xác nhận bởi')
                            ->placeholder('—'),

                        TextEntry::make('confirmed_at')
                            ->label('Xác nhận lúc')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),
                ]),
        ]);
    }
}
