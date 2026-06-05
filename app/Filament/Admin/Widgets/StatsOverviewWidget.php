<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MissingChild;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Clue;
use App\Models\FaceMatchResult;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = MissingChild::count();
        $pending = MissingChild::where('status', 'PENDING')->count();
        $newIn7Days = MissingChild::where('created_at', '>=', now()->subDays(7))->count();
        $found = MissingChild::where('status', 'FOUND')->count();
        $foundRate = $total > 0 ? round(($found / $total) * 100, 1) : 0;
        $totalClues = Clue::count();

        return [
            Stat::make('Tổng hồ sơ', $total)
                ->description('Tất cả hồ sơ trong hệ thống')
                ->color('primary'),

            Stat::make('Chờ duyệt', $pending)
                ->description('Hồ sơ đang chờ admin duyệt')
                ->color($pending > 0 ? 'danger' : 'success'),

            Stat::make('Mới 7 ngày', $newIn7Days)
                ->description('Hồ sơ được tạo trong 7 ngày gần đây')
                ->color('info'),

            Stat::make('Tỷ lệ tìm thấy', $foundRate . '%')
                ->description('Số FOUND / tổng hồ sơ')
                ->color('success'),

            Stat::make('Tổng manh mối', $totalClues)
                ->description('Từ cộng đồng gửi lên')
                ->color('info'),


        ];
    }
}