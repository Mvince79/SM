<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('phone')
                    ->tel(),
                DateTimePicker::make('phone_verified_at'),
                TextInput::make('password')
                    ->password(),
                TextInput::make('google_id'),
                TextInput::make('avatar_url')
                    ->url(),
                Select::make('role')
                    ->options(['ADMIN' => 'A d m i n', 'MODERATOR' => 'M o d e r a t o r', 'USER' => 'U s e r'])
                    ->default('USER')
                    ->required(),
                Select::make('status')
                    ->options(['ACTIVE' => 'A c t i v e', 'BANNED' => 'B a n n e d', 'PENDING' => 'P e n d i n g'])
                    ->default('ACTIVE')
                    ->required(),
                Textarea::make('ban_reason')
                    ->columnSpanFull(),
                TextInput::make('province_id')
                    ->numeric(),
                TextInput::make('notify_lat')
                    ->numeric(),
                TextInput::make('notify_lng')
                    ->numeric(),
                TextInput::make('notify_radius_km')
                    ->required()
                    ->numeric()
                    ->default(50),
                TextInput::make('otp_code'),
                DateTimePicker::make('otp_expires_at'),
                DateTimePicker::make('last_active_at'),
            ]);
    }
}
