<?php

namespace App\Providers;

use App\Models\FoundChild;
use App\Policies\FoundChildPolicy;
use App\Policies\VolunteerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        FoundChild::class => FoundChildPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('access-volunteer-features', [VolunteerPolicy::class, 'access']);   // ← thêm
    }
}