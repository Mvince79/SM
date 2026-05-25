<?php

namespace App\Filament\Admin\Resources\FoundChildResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $recordTitleAttribute = 'path_original';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path_original')
                ->label('Ảnh')
                ->image()
                ->disk('public')
                ->directory(fn($record) => 'found_children/' . ($record?->getKey() ?? 'tmp') . '/original')
                ->required(),
            Toggle::make('is_primary')->label('Ảnh chính'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path_original')->label('Ảnh')->disk('public')->circular(false)->toggleable(false),
                TextColumn::make('file_size')->label('Kích thước'),
                TextColumn::make('mime_type')->label('MIME'),
                TextColumn::make('is_primary')
                    ->label('Chính')
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Có' : 'Không'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
