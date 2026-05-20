<?php

namespace App\Filament\Admin\Resources\Clues\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('missing_id')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clue_type')
                    ->badge(),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('lat')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('lng')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('gps_shared_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('location_accuracy_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('occurred_at')
                    ->dateTime()
                    ->placeholder('-'),
                ImageEntry::make('image_path')
                    ->placeholder('-'),
                TextEntry::make('is_verified')
                    ->numeric(),
                TextEntry::make('verified_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('is_anonymous')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
