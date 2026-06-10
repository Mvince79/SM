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
        $data = Cache::remember('missing_by_province_chart', 300, function () {
            return MissingChild::query()
                ->join('provinces', 'missing_children.province_id', '=', 'provinces.id')
                ->selectRaw('provinces.name as province_name, COUNT(*) as total')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        });

        // Màu đỏ giảm dần — rõ ràng hơn khi nhìn cột cao/thấp
        $alphas = [0.9, 0.82, 0.74, 0.66, 0.58, 0.50, 0.44, 0.38, 0.32, 0.26];
        $colors = array_map(
            fn($a) => "rgba(255, 59, 48, {$a})",
            array_slice($alphas, 0, $data->count())
        );

        return [
            'datasets' => [
                [
                    'label' => 'Số hồ sơ',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => $colors,
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