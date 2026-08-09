<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SprintFiveScheduleTest extends TestCase
{
    public function test_horizon_metrics_and_case_expiration_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());

        $snapshot = $events->first(
            fn ($event) => str_contains($event->command ?? '', 'horizon:snapshot'),
        );
        $archive = $events->first(
            fn ($event) => str_contains($event->command ?? '', 'cases:archive-expired'),
        );

        $this->assertNotNull($snapshot);
        $this->assertSame('*/5 * * * *', $snapshot->expression);
        $this->assertNotNull($archive);
        $this->assertSame('0 1 * * *', $archive->expression);
    }
}
