<?php

namespace App\Providers;

use App\Livewire\Volunteer\AssignmentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        $this->app->singleton(\App\Services\QrCodeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policy
        Gate::policy(
            \App\Models\MissingChild::class,
            \App\Policies\MissingChildPolicy::class
        );

        // Register Observer
        \App\Models\MissingChild::observe(
            \App\Observers\MissingChildObserver::class
        );

        \App\Models\Photo::observe(
            \App\Observers\PhotoObserver::class
        );

        // Register Livewire component
        Livewire::component(
            'volunteer.assignment-board',
            AssignmentBoard::class
        );
    }
}