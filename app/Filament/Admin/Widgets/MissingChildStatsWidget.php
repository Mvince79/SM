<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MissingChild;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class MissingChildStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $missingChild = MissingChild::withCount([
            'clues',
            'photos',
            'faceMatchResults',
            'comments',
        ])->find($this->record?->id);

        return [
            Stat::make('Tổng manh mối', $missingChild?->clues_count ?? 0)
                ->description('Số manh mối đã gửi')
                ->color('info'),

            Stat::make('Ảnh đã upload', $missingChild?->photos_count ?? 0)
                ->description('Tổng ảnh của hồ sơ')
                ->color('success'),

            Stat::make('Kết quả AI face match', $missingChild?->face_match_results_count ?? 0)
                ->description('Số kết quả AI')
                ->color('warning'),

            Stat::make('Bình luận', $missingChild?->comments_count ?? 0)
                ->description('Bình luận đã duyệt')
                ->color('gray'),
        ];
    }
}