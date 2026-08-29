<?php

namespace App\Filament\Admin\Resources\Activities;

use App\Filament\Admin\Resources\Activities\Pages\ListActivities;
use App\Filament\Admin\Resources\Activities\Schemas\ActivityForm;
use App\Filament\Admin\Resources\Activities\Tables\ActivitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    // Đổi sang icon danh sách nhật ký phù hợp hơn
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'description';

    // Đổi tên nhãn hiển thị trên Sidebar và tiêu đề sang Tiếng Việt
    protected static ?string $navigationLabel = 'Nhật Ký Hoạt Động';
    protected static ?string $modelLabel = 'Nhật ký hoạt động';
    protected static ?string $pluralModelLabel = 'Nhật ký hoạt động';

    // D-04 — activity_log là bất biến: KHÔNG cho tạo/sửa/xóa qua UI
    // (tránh admin can thiệp vào bằng chứng hành vi).
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

    public static function form(Schema $schema): Schema
    {
        return ActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
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
            'index' => ListActivities::route('/'),
        ];
    }
}