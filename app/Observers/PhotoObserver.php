<?php

namespace App\Observers;

use App\Jobs\ProcessPhotoJob;
use App\Models\Photo;

class PhotoObserver
{
    public function created(Photo $photo): void
    {
        // Dispatch the processing job (stub) so queue setup can be verified.
        ProcessPhotoJob::dispatch($photo->id);
    }
}
