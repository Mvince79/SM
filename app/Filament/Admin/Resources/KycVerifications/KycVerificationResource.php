<?php

namespace App\Filament\Admin\Resources\KycVerifications;

use App\Filament\Admin\Resources\KycVerifications\Pages\ListKycVerifications;
use App\Filament\Admin\Resources\KycVerifications\Pages\ViewKycVerification;
use App\Filament\Admin\Resources\KycVerifications\Schemas\KycVerificationInfolist;
use App\Filament\Admin\Resources\KycVerifications\Tables\KycVerificationsTable;
use App\Models\KycVerification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class KycVerificationResource extends Resource
{
    protected static ?string $model = KycVerification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Xác Thực Danh Tính (KYC)';

    protected static ?string $modelLabel = 'Xác Thực Danh Tính (KYC)';

    protected static ?string $pluralModelLabel = 'Xác Thực Danh Tính (KYC)';

    public static function getNavigationGroup(): ?string
    {
        return 'Xác thực';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Cache::remember('badge_kyc_pending', 300, function () {
            return KycVerification::query()
                ->where('status', 'PENDING')
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : 'gray';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'reviewedBy']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KycVerificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KycVerificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKycVerifications::route('/'),
            'view' => ViewKycVerification::route('/{record}'),
        ];
    }
}
