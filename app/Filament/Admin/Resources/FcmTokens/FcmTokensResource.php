<?php

namespace App\Filament\Admin\Resources\FcmTokens;

use App\Filament\Admin\Resources\FcmTokens\Pages\ListFcmTokens;
use App\Filament\Admin\Resources\FcmTokens\Tables\FcmTokensTable;
use App\Models\FcmToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class FcmTokensResource extends Resource
{
    protected static ?string $model = FcmToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Token thiết bị';

    protected static ?string $modelLabel = 'Token thiết bị';

    protected static ?string $pluralModelLabel = 'Token thiết bị';

    public static function getNavigationGroup(): ?string
    {
        return 'Thiết bị';
    }

    // FCM Tokens là dữ liệu nhạy cảm/dấu vết thiết bị: chỉ xem, không tạo/sửa/xóa.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return FcmTokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFcmTokens::route('/'),
        ];
    }
}
