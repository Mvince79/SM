<?php

namespace App\Filament\Admin\Resources\ThirdPartyVerifierProfiles;

use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Pages\CreateThirdPartyVerifierProfile;
use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Pages\EditThirdPartyVerifierProfile;
use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Pages\ListThirdPartyVerifierProfiles;
use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Schemas\ThirdPartyVerifierProfileForm;
use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Tables\ThirdPartyVerifierProfilesTable;
use App\Models\ThirdPartyVerifierProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ThirdPartyVerifierProfileResource extends Resource
{
    protected static ?string $model = ThirdPartyVerifierProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Hồ sơ Verifier';

    protected static ?string $modelLabel = 'hồ sơ Verifier';

    protected static ?string $pluralModelLabel = 'Hồ sơ Verifier';

    protected static string|UnitEnum|null $navigationGroup = 'Bàn giao an toàn';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ThirdPartyVerifierProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThirdPartyVerifierProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThirdPartyVerifierProfiles::route('/'),
            'create' => CreateThirdPartyVerifierProfile::route('/create'),
            'edit' => EditThirdPartyVerifierProfile::route('/{record}/edit'),
        ];
    }
}
