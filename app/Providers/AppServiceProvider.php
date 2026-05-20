<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\OtpService::class);
        $this->app->singleton(\App\Services\RateLimiterService::class);
        $this->app->singleton(\App\Services\GoogleAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\MissingChild::class, \App\Policies\MissingChildPolicy::class);

        // Register Observer
        \App\Models\MissingChild::observe(\App\Observers\MissingChildObserver::class);
    }
}
