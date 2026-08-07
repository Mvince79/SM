<?php

namespace App\Filament\Admin\Resources\FoundChildResource\RelationManagers;

use App\Rules\SecureImage;
use App\Support\SecureUploadFilename;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                ->directory(fn ($record) => 'found_children/'.($record?->getKey() ?? 'tmp').'/original')
                ->maxSize(5120)
                ->rules([new SecureImage])
                ->getUploadedFileNameForStorageUsing(
                    fn (TemporaryUploadedFile $file): string => SecureUploadFilename::for($file),
                )
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
                TextColumn::make('ai_status')
                    ->label('AI')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Dang cho AI',
                        'processing' => 'Dang xu ly',
                        'done' => 'Da xu ly',
                        'failed' => 'That bai',
                        default => $state ?: 'Dang cho AI',
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'done' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
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
