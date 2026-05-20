<?php

namespace App\Filament\Admin\Resources\Clues;

use App\Filament\Admin\Resources\Clues\Pages\CreateClue;
use App\Filament\Admin\Resources\Clues\Pages\EditClue;
use App\Filament\Admin\Resources\Clues\Pages\ListClues;
use App\Filament\Admin\Resources\Clues\Pages\ViewClue;
use App\Filament\Admin\Resources\Clues\Schemas\ClueForm;
use App\Filament\Admin\Resources\Clues\Schemas\ClueInfolist;
use App\Filament\Admin\Resources\Clues\Tables\CluesTable;
use App\Models\Clue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClueResource extends Resource
{
    protected static ?string $model = Clue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ClueForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CluesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClues::route('/'),
            'create' => CreateClue::route('/create'),
            'view' => ViewClue::route('/{record}'),
            'edit' => EditClue::route('/{record}/edit'),
        ];
    }
}
