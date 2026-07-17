<?php

namespace Tests\Unit;

use App\Jobs\DispatchMatchConfirmedNotificationsJob;
use App\Models\FaceMatchResult;
use App\Observers\FaceMatchResultObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FaceMatchResultObserverTest extends TestCase
{
    public function test_it_dispatches_notification_job_when_status_becomes_confirmed(): void
    {
        Queue::fake();
        $match = $this->changedMatch(['status' => 'CONFIRMED']);

        app(FaceMatchResultObserver::class)->updated($match);

        Queue::assertPushed(
            DispatchMatchConfirmedNotificationsJob::class,
            fn (DispatchMatchConfirmedNotificationsJob $job): bool => $job->faceMatchResultId === 123 && $job->queue === 'notifications'
        );
    }

    public function test_it_does_not_dispatch_when_another_field_changes(): void
    {
        Queue::fake();
        $match = $this->changedMatch(['score' => 0.95]);

        app(FaceMatchResultObserver::class)->updated($match);

        Queue::assertNothingPushed();
    }

    private function changedMatch(array $changes): FaceMatchResult
    {
        $match = new FaceMatchResult;
        $match->setRawAttributes([
            'id' => 123,
            'status' => 'PENDING',
            'score' => 0.90,
        ], true);
        $match->forceFill($changes);
        $match->syncChanges();

        return $match;
    }
}
