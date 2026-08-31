<?php

namespace App\Providers;

use App\Settings\SettingService;
use Illuminate\Support\ServiceProvider;

/**
 * Đăng ký SettingService và ghi đè config() tại runtime từ DB (hoặc default)
 * ngay khi app khởi động, để mọi chỗ đọc config(...) cũ nhận giá trị do admin
 * chỉnh trên giao diện. Có guard bên trong nên không nổ khi chưa migrate.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class, fn (): SettingService => new SettingService());
    }

    public function boot(): void
    {
        $this->app->make(SettingService::class)->applyToConfig();
    }
}
