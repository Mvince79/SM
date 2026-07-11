<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use App\Models\KycVerification;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class KycPendingStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $pending = Cache::remember(
            'badge_kyc_pending',
            300,
            fn() => KycVerification::query()
                ->where('status', 'PENDING')
                ->count(),
        );

        return [
            Stat::make('KYC chờ duyệt', $pending)
                ->description('Hồ sơ đang chờ Admin duyệt')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->url(
                    KycVerificationResource::getUrl('index', [
                        'filters' => [
                            'status' => [
                                'value' => 'PENDING',
                            ],
                        ],
                    ])
                ),
        ];
    }
}
