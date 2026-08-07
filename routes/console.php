<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('views:flush')->everyFiveMinutes();
Schedule::command('notifications:scan-unread-critical')->everyFiveMinutes();

// Persist Horizon throughput/runtime metrics for the dashboard.
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Archive cases only after the configured retention window has elapsed.
Schedule::command('cases:archive-expired')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();
