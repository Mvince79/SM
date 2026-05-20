<?php

namespace App\Filament\Admin\Resources\FoundChildren\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FoundChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('province_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('district')
                    ->searchable(),
                TextColumn::make('ward')
                    ->searchable(),
                TextColumn::make('address_detail')
                    ->searchable(),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('found_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('found_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('gender_estimate')
                    ->badge(),
                TextColumn::make('age_estimate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_location')
                    ->searchable(),
                TextColumn::make('handover_agency')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ai_processed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
