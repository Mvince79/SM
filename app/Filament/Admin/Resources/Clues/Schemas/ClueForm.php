<?php

namespace App\Filament\Admin\Resources\Clues\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('missing_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric(),
                Select::make('clue_type')
                    ->options([
            'SIGHTING' => 'S i g h t i n g',
            'RUMOR' => 'R u m o r',
            'PHOTO' => 'P h o t o',
            'VIDEO' => 'V i d e o',
            'OTHER' => 'O t h e r',
        ])
                    ->default('SIGHTING')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                DateTimePicker::make('gps_shared_at'),
                TextInput::make('location_accuracy_m')
                    ->numeric(),
                DateTimePicker::make('occurred_at'),
                FileUpload::make('image_path')
                    ->image(),
                TextInput::make('is_verified')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('verified_by')
                    ->numeric(),
                TextInput::make('is_anonymous')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
