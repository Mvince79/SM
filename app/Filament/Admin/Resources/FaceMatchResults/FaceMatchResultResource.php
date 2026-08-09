<?php

namespace App\Filament\Admin\Resources\FaceMatchResults;


use App\Filament\Admin\Resources\FaceMatchResults\Pages\ListFaceMatchResults;
use App\Filament\Admin\Resources\FaceMatchResults\Pages\ViewFaceMatchResult;
use App\Filament\Admin\Resources\FaceMatchResults\Schemas\FaceMatchResultForm;
use App\Filament\Admin\Resources\FaceMatchResults\Tables\FaceMatchResultsTable;
use App\Filament\Admin\Resources\FaceMatchResults\Schemas\FaceMatchResultInfolist;
use App\Models\FaceMatchResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class FaceMatchResultResource extends Resource
{
    protected static ?string $model = FaceMatchResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Đối chiếu khuôn mặt AI';

    protected static ?string $modelLabel = 'kết quả đối chiếu khuôn mặt';

    protected static ?string $pluralModelLabel = 'kết quả đối chiếu khuôn mặt';
    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý AI';
    }

    // ── Navigation Badge: đếm PENDING ─────────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_face_match_pending', 300, function () {
            return FaceMatchResult::where('status', 'PENDING')->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    // ── Eager loading chống N+1 ───────────────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'missingChild:id,full_name,case_code',
                'foundChild:id,province_id',
                'missingPhoto:id,path_thumb,path_original',
                'foundPhoto:id,path_thumb,path_original',
                'confirmedBy:id,full_name',
            ]);
    }

    // ── Form ──────────────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return FaceMatchResultForm::configure($schema);
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return FaceMatchResultsTable::configure($table);
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => ListFaceMatchResults::route('/'),
            'view' => ViewFaceMatchResult::route('/{record}'),
        ];
    }
    public static function infolist(Schema $schema): Schema
    {
        return FaceMatchResultInfolist::configure($schema);
    }
}
