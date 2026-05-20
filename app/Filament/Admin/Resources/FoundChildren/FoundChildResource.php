<?php

namespace App\Filament\Admin\Resources\FoundChildren;

use App\Filament\Admin\Resources\FoundChildren\Pages\CreateFoundChild;
use App\Filament\Admin\Resources\FoundChildren\Pages\EditFoundChild;
use App\Filament\Admin\Resources\FoundChildren\Pages\ListFoundChildren;
use App\Filament\Admin\Resources\FoundChildren\Pages\ViewFoundChild;
use App\Filament\Admin\Resources\FoundChildren\Schemas\FoundChildForm;
use App\Filament\Admin\Resources\FoundChildren\Schemas\FoundChildInfolist;
use App\Filament\Admin\Resources\FoundChildren\Tables\FoundChildrenTable;
use App\Models\FoundChild;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FoundChildResource extends Resource
{
    protected static ?string $model = FoundChild::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FoundChildForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FoundChildInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoundChildrenTable::configure($table);
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
            'index' => ListFoundChildren::route('/'),
            'create' => CreateFoundChild::route('/create'),
            'view' => ViewFoundChild::route('/{record}'),
            'edit' => EditFoundChild::route('/{record}/edit'),
        ];
    }
}
