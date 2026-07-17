<?php

namespace App\Services\Notification\Strategies;

use App\Models\FaceMatchResult;
use App\Models\NotificationEvent;
use App\Services\Notification\Contracts\NotificationStrategy;
use UnexpectedValueException;

abstract class AbstractFaceMatchConfirmedStrategy implements NotificationStrategy
{
    protected function match(NotificationEvent $event): FaceMatchResult
    {
        $subject = $event->subject;

        if (! $subject instanceof FaceMatchResult) {
            throw new UnexpectedValueException('FACE_MATCH_CONFIRMED yêu cầu subject là FaceMatchResult.');
        }

        $subject->loadMissing('missingChild');

        return $subject;
    }

    protected function caseLabel(FaceMatchResult $match): string
    {
        return $match->missingChild?->case_code
            ?? $match->missingChild?->full_name
            ?? "#{$match->missing_id}";
    }

    protected function caseUrl(FaceMatchResult $match): string
    {
        return route('missing.detail', ['id' => $match->missing_id]);
    }
}
