<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FaceMatchResultForm
{
    public static function configure(Schema $schema): Schema
    {
        // FaceMatchResult là readonly resource — form chỉ dùng khi cần
        // Confirm/Dismiss xử lý qua table record actions, không phải form
        return $schema->components([
            TextInput::make('missing_id')
                ->label('Missing ID')
                ->required()
                ->numeric(),

            TextInput::make('found_id')
                ->label('Found ID')
                ->required()
                ->numeric(),

            TextInput::make('score')
                ->label('Điểm khớp (0–1)')
                ->required()
                ->numeric()
                ->step(0.0001),

            Select::make('source')
                ->label('Nguồn')
                ->options([
                    'REALTIME' => 'Realtime',
                    'AGE_PROGRESSION' => 'Age Progression',
                    'MANUAL' => 'Manual',
                ])
                ->default('REALTIME')
                ->required(),

            TextInput::make('model_used')
                ->label('Model AI'),

            Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'PENDING' => 'Chờ xử lý',
                    'CONFIRMED' => 'Đã xác nhận',
                    'DISMISSED' => 'Đã bác bỏ',
                ])
                ->default('PENDING')
                ->required(),

            DateTimePicker::make('confirmed_at')
                ->label('Xác nhận lúc'),
        ]);
    }
}
