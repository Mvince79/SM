<?php

namespace App\Filament\Admin\Resources\MissingChildren;

use App\Filament\Admin\Resources\MissingChildren\Pages\CreateMissingChild;
use App\Filament\Admin\Resources\MissingChildren\Pages\EditMissingChild;
use App\Filament\Admin\Resources\MissingChildren\Pages\ListMissingChildren;
use App\Filament\Admin\Resources\MissingChildren\Pages\ViewMissingChild;
use App\Filament\Admin\Resources\MissingChildren\Schemas\MissingChildForm;
use App\Filament\Admin\Resources\MissingChildren\Schemas\MissingChildInfolist;
use App\Filament\Admin\Resources\MissingChildren\Tables\MissingChildrenTable;
use App\Models\MissingChild;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MissingChildResource extends Resource
{
    protected static ?string $model = MissingChild::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Trẻ mất tích';
    protected static ?string $modelLabel = 'Trẻ mất tích';
    protected static ?string $pluralModelLabel = 'Trẻ mất tích';

    public static function form(Schema $schema): Schema
    {
        return MissingChildForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MissingChildInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MissingChildrenTable::configure($table);
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
            'index' => ListMissingChildren::route('/'),
            'create' => CreateMissingChild::route('/create'),
            'view' => ViewMissingChild::route('/{record}'),
            'edit' => EditMissingChild::route('/{record}/edit'),
        ];
    }
}
