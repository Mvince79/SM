<?php

use App\Models\NotificationEvent;
use App\Services\Notification\Strategies\FaceMatchConfirmedForAdminStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForFamilyStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForVerifierStrategy;

return [
    'strategies' => [
        NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => [
            'USER' => FaceMatchConfirmedForFamilyStrategy::class,
            'VOLUNTEER' => FaceMatchConfirmedForFamilyStrategy::class,
            'THIRD_PARTY_VERIFIER' => FaceMatchConfirmedForVerifierStrategy::class,
            'ADMIN' => FaceMatchConfirmedForAdminStrategy::class,
            'MODERATOR' => FaceMatchConfirmedForAdminStrategy::class,
        ],
    ],
];
