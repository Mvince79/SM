<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\EscalationQueueWidget;
use App\Filament\Admin\Widgets\KycPendingStatsWidget;
use App\Filament\Admin\Widgets\MissingByProvinceChart;
use App\Filament\Admin\Widgets\RecentMissingWidget;
use App\Filament\Admin\Widgets\StatsOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('TìmTrẻ.vn Admin')
            ->brandLogo(null)
            ->favicon(null)
            // Palette đồng bộ với client frontend (resources/css/app.css):
            // Carmine × Deep Navy × Forest — institutional, không neon.
            ->colors([
                'primary' => Color::hex('#b9293f'), // Carmine trầm (duyệt / thương hiệu)
                'danger' => Color::hex('#dc2626'),  // Red-600 (từ chối / cảnh báo) — tách biệt với primary
                'success' => Color::hex('#2f6d52'), // Forest (đã xác thực / khớp)
                'info' => Color::hex('#1c3a5e'),     // Deep Navy (thông tin / nguồn)
                'warning' => Color::hex('#f59e0b'),  // Amber (chờ duyệt / cần kiểm tra)
            ])
            ->font('Be Vietnam Pro')
            ->darkMode(false)
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\Filament\Admin\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\Filament\Admin\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Admin/Widgets'),
                for: 'App\Filament\Admin\Widgets'
            )
            ->widgets([
                EscalationQueueWidget::class,
                StatsOverviewWidget::class,
                KycPendingStatsWidget::class,
                MissingByProvinceChart::class,
                RecentMissingWidget::class,

                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // [Item 9] Badge số thông báo chưa đọc trên topbar admin (tĩnh, không JS).
            // Xoá khối này để rollback. Dùng component riêng, không nhét chuông client
            // (cần notifications.js chỉ có ở layout client).
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('components.admin.notification-badge')->render(),
            );
    }
}
