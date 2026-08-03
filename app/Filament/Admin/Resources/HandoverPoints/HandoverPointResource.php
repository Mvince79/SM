<?php

namespace App\Filament\Admin\Resources\HandoverPoints;

use App\Filament\Admin\Resources\HandoverPoints\Pages\CreateHandoverPoint;
use App\Filament\Admin\Resources\HandoverPoints\Pages\EditHandoverPoint;
use App\Filament\Admin\Resources\HandoverPoints\Pages\ListHandoverPoints;
use App\Filament\Admin\Resources\HandoverPoints\Pages\ViewHandoverPoint;
use App\Filament\Admin\Resources\HandoverPoints\Schemas\HandoverPointForm;
use App\Filament\Admin\Resources\HandoverPoints\Schemas\HandoverPointInfolist;
use App\Filament\Admin\Resources\HandoverPoints\Tables\HandoverPointsTable;
use App\Models\HandoverPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HandoverPointResource extends Resource
{
    protected static ?string $model = HandoverPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static ?string $navigationLabel = 'Điểm Bàn Giao An Toàn';
    protected static ?string $modelLabel = 'Điểm bàn giao';
    protected static ?string $pluralModelLabel = 'Điểm bàn giao';

    public static function form(Schema $schema): Schema
    {
        return HandoverPointForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HandoverPointInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HandoverPointsTable::configure($table);
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
            'index' => ListHandoverPoints::route('/'),
            'create' => CreateHandoverPoint::route('/create'),
            'view' => ViewHandoverPoint::route('/{record}'),
            'edit' => EditHandoverPoint::route('/{record}/edit'),
        ];
    }
}
