<?php

namespace App\Observers;

use App\Jobs\DispatchMatchConfirmedNotificationsJob;
use App\Models\FaceMatchResult;

class FaceMatchResultObserver
{
    public function updated(FaceMatchResult $match): void
    {
        if ($match->wasChanged('status') && $match->status === 'CONFIRMED') {
            DispatchMatchConfirmedNotificationsJob::dispatch($match->getKey())->afterCommit();
        }
    }
}
