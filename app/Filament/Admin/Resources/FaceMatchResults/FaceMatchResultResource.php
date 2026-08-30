<?php

namespace App\Filament\Admin\Resources\FaceMatchResults;

use App\Filament\Admin\Resources\FaceMatchResults\Pages\ListFaceMatchResults;
use App\Filament\Admin\Resources\FaceMatchResults\Pages\ViewFaceMatchResult;
use App\Filament\Admin\Resources\FaceMatchResults\Schemas\FaceMatchResultForm;
use App\Filament\Admin\Resources\FaceMatchResults\Schemas\FaceMatchResultInfolist;
use App\Filament\Admin\Resources\FaceMatchResults\Tables\FaceMatchResultsTable;
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

    // ── Navigation Badge: đếm PENDING còn thao tác được ─────────────────────────
    // CHỈ đếm PENDING của những hồ sơ CHƯA kết thúc (missing/found child không
    // nằm trong CLOSED_STATUSES và chưa bị xoá) — hồ sơ đã đoàn tụ/đóng/từ chối/
    // xoá không còn nằm trong hàng đợi "so khớp AI" nữa.
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_face_match_pending', 300, function () {
            return FaceMatchResult::query()
                ->where('status', 'PENDING')
                ->whereHas('missingChild', fn (Builder $q) => $q->notResolved())
                ->whereHas('foundChild', fn (Builder $q) => $q->notResolved())
                ->count();
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
            // Ẩn mọi kết quả đối chiếu thuộc hồ sơ ĐÃ KẾT THÚC (REUNITED / CLOSED /
            // REJECTED / ARCHIVED / UNREACHABLE_FAMILY) hoặc ĐÃ XOÁ (soft/hard):
            // admin "Đối chiếu khuôn mặt AI" chỉ thao tác trên hồ sơ đang active,
            // không còn gợi ý "so khớp" treo cho hồ sơ đã xong việc. whereHas tự
            // loại hồ sơ soft-deleted (global scope) và hồ sơ bị force-delete
            // (quan hệ không còn tồn tại).
            ->whereHas('missingChild', fn (Builder $q) => $q->notResolved())
            ->whereHas('foundChild', fn (Builder $q) => $q->notResolved())
            ->with([
                'missingChild:id,full_name,case_code',
                'foundChild:id,province_id,found_date,status',
                'foundChild.province',
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
