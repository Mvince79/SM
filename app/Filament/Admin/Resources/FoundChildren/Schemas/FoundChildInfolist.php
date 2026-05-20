<?php

namespace App\Filament\Admin\Resources\FoundChildren\Schemas;

use App\Models\FoundChild;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FoundChildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('province_id')
                    ->numeric(),
                TextEntry::make('district')
                    ->placeholder('-'),
                TextEntry::make('ward')
                    ->placeholder('-'),
                TextEntry::make('address_detail')
                    ->placeholder('-'),
                TextEntry::make('lat')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('lng')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('found_date')
                    ->date(),
                TextEntry::make('found_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('gender_estimate')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('age_estimate')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('condition_desc')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('clothes_desc')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('special_marks')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('current_location')
                    ->placeholder('-'),
                TextEntry::make('handover_agency')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('ai_processed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (FoundChild $record): bool => $record->trashed()),
            ]);
    }
}
