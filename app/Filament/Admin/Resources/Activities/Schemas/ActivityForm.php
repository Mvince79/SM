<?php

namespace App\Filament\Admin\Resources\Activities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('log_name')
                    ->default('default'),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('subject_type'),
                TextInput::make('subject_id')
                    ->numeric(),
                TextInput::make('event'),
                TextInput::make('causer_type'),
                TextInput::make('causer_id')
                    ->numeric(),
                TextInput::make('properties'),
                TextInput::make('batch_uuid'),
            ]);
    }
}
