<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MissingChild;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class MissingByProvinceChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 tỉnh có nhiều hồ sơ nhất';

    protected ?string $maxHeight = '280px';
    protected function getData(): array
    {
        // FIX: cache 5 phút (300 giây) thay vì 1 tiếng
        $data = Cache::remember('missing_by_province_chart', 300, function () {
            return MissingChild::query()
                ->join('provinces', 'missing_children.province_id', '=', 'provinces.id')
                ->selectRaw('provinces.name as province_name, COUNT(*) as total')
                ->groupBy('provinces.id', 'provinces.name') // groupBy id để tránh SQL mode ONLY_FULL_GROUP_BY
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Số hồ sơ',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        'rgba(255, 59, 48, 0.8)',
                        'rgba(255, 59, 48, 0.7)',
                        'rgba(255, 59, 48, 0.6)',
                        'rgba(255, 59, 48, 0.5)',
                        'rgba(255, 59, 48, 0.45)',
                        'rgba(255, 59, 48, 0.4)',
                        'rgba(255, 59, 48, 0.35)',
                        'rgba(255, 59, 48, 0.3)',
                        'rgba(255, 59, 48, 0.25)',
                        'rgba(255, 59, 48, 0.2)',
                    ],
                    'borderColor' => 'rgba(255, 59, 48, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $data->pluck('province_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ];
    }
}