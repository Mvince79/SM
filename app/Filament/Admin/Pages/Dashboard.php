<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\KycPendingStatsWidget;
use App\Filament\Admin\Widgets\MissingByProvinceChart;
use App\Filament\Admin\Widgets\RecentMissingWidget;
use App\Filament\Admin\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Bảng điều khiển';

    protected static ?string $title = 'Bảng điều khiển';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            KycPendingStatsWidget::class,
            MissingByProvinceChart::class,
            RecentMissingWidget::class,
        ];
    }
}
