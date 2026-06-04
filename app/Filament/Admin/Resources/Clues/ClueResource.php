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
use Illuminate\Support\Facades\Cache;

use Illuminate\Database\Eloquent\Builder;

class ClueResource extends Resource
{
    protected static ?string $model = Clue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Manh mối';
    protected static ?string $modelLabel = 'Manh mối';
    protected static ?string $pluralModelLabel = 'Manh mối';

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'missingChild:id,full_name,case_code',
                'user:id,full_name',
                'verifiedBy:id,full_name',
            ]);
    }

    // ─── Navigation Badge ────────────────────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_clue_unverified', 300, function () {
            return static::getModel()::where('is_verified', false)->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        $count = Cache::remember('badge_clue_unverified', 300, function () {
            return static::getModel()::where('is_verified', false)->count();
        });

        return $count > 10 ? 'danger' : 'warning';
    }
}
