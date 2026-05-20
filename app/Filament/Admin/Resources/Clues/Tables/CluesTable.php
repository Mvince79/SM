<?php

namespace App\Filament\Admin\Resources\Clues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CluesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('missing_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clue_type')
                    ->badge(),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gps_shared_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location_accuracy_m')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
                ImageColumn::make('image_path'),
                TextColumn::make('is_verified')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('is_anonymous')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
