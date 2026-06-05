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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\MissingChildren\RelationManagers\CommentsRelationManager;
use App\Filament\Admin\Resources\MissingChildren\RelationManagers\ActivityLogRelationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


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

    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_missing_pending', 300, function () {
            return MissingChild::where('status', 'PENDING')->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = (int) Cache::get('badge_missing_pending', 0);

        return $count > 10 ? 'danger' : 'warning';
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
            CommentsRelationManager::class,
            ActivityLogRelationManager::class
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'province:id,name',
                'user:id,full_name',
                'approvedBy:id,full_name',
                'primaryPhoto',
            ])
            ->withCount(['clues', 'comments']);
    }



    // ─── Global Search ───────────────────────────────────────────────────────
    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'case_code'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Mã hồ sơ' => $record->case_code,
            'Trạng thái' => match ($record->status) {
                'PENDING' => 'Chờ duyệt',
                'APPROVED' => 'Đã duyệt',
                'FOUND' => 'Đã tìm thấy',
                'REJECTED' => 'Từ chối',
                'CLOSED' => 'Đóng',
                default => $record->status,
            },
        ];
    }
}
