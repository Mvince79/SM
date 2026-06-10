<?php

namespace App\Filament\Admin\Resources\MissingChildren\Widgets;

use App\Models\MissingChild;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MissingChildStatsWidget extends BaseWidget
{
    public ?MissingChild $record = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // 1 query thay vì 3 query riêng
        $record = MissingChild::withCount([
            'clues',
            'photos',
            'comments' => fn($q) => $q->where('is_hidden', 0),
        ])->find($this->record->id);

        $aiCount = 0;
        if (class_exists(\App\Models\FaceMatchResult::class)) {
            $aiCount = \App\Models\FaceMatchResult::where('missing_id', $this->record->id)
                ->where('is_matched', 1)
                ->count();
        }

        $clueCount = $record?->clues_count ?? 0;
        $photoCount = $record?->photos_count ?? 0;
        $commentCount = $record?->comments_count ?? 0;

        return [
            Stat::make('Manh mối', $clueCount)
                ->description('Số manh mối đã gửi')
                ->color($clueCount > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-light-bulb'),

            Stat::make('Ảnh hồ sơ', $photoCount)
                ->description('Tổng ảnh đã upload')
                ->color($photoCount > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-photo'),

            Stat::make('AI Face Match', $aiCount)
                ->description('Kết quả AI khớp')
                ->color($aiCount > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-cpu-chip'),

            Stat::make('Bình luận', $commentCount)
                ->description('Bình luận đã duyệt')
                ->color($commentCount > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}