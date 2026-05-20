<?php

namespace App\Filament\Admin\Resources\FoundChildren\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class FoundChildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('province_id')
                    ->required()
                    ->numeric(),
                TextInput::make('district'),
                TextInput::make('ward'),
                TextInput::make('address_detail'),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                DatePicker::make('found_date')
                    ->required(),
                TimePicker::make('found_time'),
                Select::make('gender_estimate')
                    ->options(['MALE' => 'M a l e', 'FEMALE' => 'F e m a l e', 'UNKNOWN' => 'U n k n o w n']),
                TextInput::make('age_estimate')
                    ->numeric(),
                Textarea::make('condition_desc')
                    ->columnSpanFull(),
                Textarea::make('clothes_desc')
                    ->columnSpanFull(),
                Textarea::make('special_marks')
                    ->columnSpanFull(),
                TextInput::make('current_location'),
                TextInput::make('handover_agency'),
                Select::make('status')
                    ->options([
            'PENDING' => 'P e n d i n g',
            'APPROVED' => 'A p p r o v e d',
            'MATCHED' => 'M a t c h e d',
            'CLOSED' => 'C l o s e d',
        ])
                    ->default('PENDING')
                    ->required(),
                DateTimePicker::make('approved_at'),
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('ai_processed_at'),
            ]);
    }
}
