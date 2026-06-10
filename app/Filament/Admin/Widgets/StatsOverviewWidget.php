<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Clue;
use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $stats = Cache::remember('dashboard_stats_overview', 300, function () {
            $total = MissingChild::count();
            $pending = MissingChild::where('status', 'PENDING')->count();
            $approved = MissingChild::where('status', 'APPROVED')->count();
            $found = MissingChild::where('status', 'FOUND')->count();
            $foundRate = $total > 0 ? round(($found / $total) * 100, 1) : 0;
            $totalClues = Clue::count();
            $totalAiMatches = FaceMatchResult::where('status', 'PENDING')->count();

            return compact(
                'total',
                'pending',
                'approved',
                'found',
                'foundRate',
                'totalClues',
                'totalAiMatches'
            );
        });

        return [
            Stat::make('Tổng hồ sơ', $stats['total'])
                ->description('Tất cả hồ sơ trong hệ thống')
                ->icon('heroicon-o-folder-open')
                ->color('primary'),

            Stat::make('Chờ duyệt', $stats['pending'])
                ->description('Hồ sơ đang chờ admin duyệt')
                ->icon('heroicon-o-clock')
                ->color($stats['pending'] > 0 ? 'danger' : 'success')
                ->url(
                    route('filament.admin.resources.missing-children.index', [
                        'tableFilters[status][value]' => 'PENDING',
                    ])
                ),

            Stat::make('Đã duyệt', $stats['approved'])
                ->description('Hồ sơ đang được tìm kiếm')
                ->icon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('Đã tìm thấy', $stats['found'])
                ->description('Tỷ lệ: ' . $stats['foundRate'] . '% tổng hồ sơ')
                ->icon('heroicon-o-heart')
                ->color('success'),

            Stat::make('Tổng manh mối', $stats['totalClues'])
                ->description('Từ cộng đồng gửi lên')
                ->icon('heroicon-o-map-pin')
                ->color('warning'),

            Stat::make('AI Match chờ xử lý', $stats['totalAiMatches'])
                ->description('Kết quả AI chưa xác nhận')
                ->icon('heroicon-o-cpu-chip')
                ->color($stats['totalAiMatches'] > 0 ? 'danger' : 'success'),
        ];
    }
}