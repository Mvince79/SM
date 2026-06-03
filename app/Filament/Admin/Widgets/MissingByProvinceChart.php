<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MissingChild;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class MissingByProvinceChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 tỉnh có nhiều hồ sơ';

    protected function getData(): array
    {
        $data = Cache::remember('missing_by_province_chart', now()->addHour(), function () {
            return MissingChild::query()
                ->join('provinces', 'missing_children.province_id', '=', 'provinces.id')
                ->selectRaw('provinces.name as province_name, COUNT(*) as total')
                ->groupBy('provinces.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Số hồ sơ',
                    'data' => $data->pluck('total')->toArray(),
                ],
            ],
            'labels' => $data->pluck('province_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}