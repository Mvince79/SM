<?php

namespace App\Filament\Admin\Resources\MissingChildren\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MissingChildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('AI')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('primaryPhoto.ai_status')
                                ->label('Trang thai AI')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'pending' => 'Dang cho AI',
                                    'processing' => 'Dang xu ly',
                                    'done' => 'Da xu ly',
                                    'failed' => 'That bai',
                                    default => 'Dang cho AI',
                                })
                                ->color(fn ($state) => match ($state) {
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'done' => 'success',
                                    'failed' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('primaryPhoto.has_face')
                                ->label('Co khuon mat')
                                ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Co' : 'Khong'),
                            TextEntry::make('face_match_results_count')
                                ->label('So ket qua AI')
                                ->state(fn ($record) => $record->faceMatchResults()->count()),
                        ]),
                    ]),
            ]);
    }
}
