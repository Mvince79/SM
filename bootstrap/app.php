<?php

use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\InternalApiKey;
use App\Http\Middleware\TouchLastActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'email.verified' => EnsureEmailVerified::class,
            'internal.key' => InternalApiKey::class,
            'role' => EnsureUserHasRole::class,
        ]);

        // Cập nhật last_active_at định kỳ (tối đa 1 lần / 5 phút) cho người dùng
        // web đã đăng nhập — xem App\Http\Middleware\TouchLastActive.
        $middleware->appendToGroup('web', TouchLastActive::class);

        $middleware->redirectGuestsTo(fn () => route('auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })
    ->create();
