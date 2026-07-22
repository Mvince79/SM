<?php

namespace App\Providers;

use App\Listeners\BroadcastNotificationReceived;
use App\Livewire\Volunteer\AssignmentBoard;
use App\Services\Notification\Contracts\FcmPushSender;
use App\Services\Notification\FcmHttpV1PushSender;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
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
        $this->app->bind(FcmPushSender::class, FcmHttpV1PushSender::class);
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

        \App\Models\FaceMatchResult::observe(
            \App\Observers\FaceMatchResultObserver::class
        );

        \App\Models\User::observe(
            \App\Observers\UserObserver::class
        );

        // Register Livewire component
        Livewire::component(
            'volunteer.assignment-board',
            AssignmentBoard::class
        );

        Event::listen(NotificationSent::class, BroadcastNotificationReceived::class);
    }
}
