<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    // Capture application exceptions, while keeping performance events disabled
    // by default to preserve the free-tier event quota.
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // This project handles identity and KYC data. Never send request headers,
    // authenticated user data, IP addresses, or SQL parameter bindings by default.
    'send_default_pii' => (bool) env('SENTRY_SEND_DEFAULT_PII', false),

    // Expected 404s are normal traffic, not production incidents.
    'ignore_exceptions' => [
        ModelNotFoundException::class,
        NotFoundHttpException::class,
    ],

    'ignore_transactions' => [
        '/health',
        '/api/health',
    ],

    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'livewire' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
        'notifications' => true,
    ],
];
