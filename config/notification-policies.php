<?php

use App\Models\NotificationEvent;
use App\Notifications\RoleChangedNotification;
use App\Services\Notification\Strategies\CaseApprovedInProvinceStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForAdminStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForFamilyStrategy;
use App\Services\Notification\Strategies\FaceMatchConfirmedForVerifierStrategy;
use App\Services\Notification\Strategies\HandoverReadyForFamilyStrategy;
use App\Services\Notification\Strategies\HandoverReadyForVerifierStrategy;

return [
    'strategies' => [
        NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => [
            'USER' => FaceMatchConfirmedForFamilyStrategy::class,
            'VOLUNTEER' => FaceMatchConfirmedForFamilyStrategy::class,
            'THIRD_PARTY_VERIFIER' => FaceMatchConfirmedForVerifierStrategy::class,
            'ADMIN' => FaceMatchConfirmedForAdminStrategy::class,
            'MODERATOR' => FaceMatchConfirmedForAdminStrategy::class,
        ],
        NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE => [
            'USER' => CaseApprovedInProvinceStrategy::class,
            'VOLUNTEER' => CaseApprovedInProvinceStrategy::class,
        ],
        NotificationEvent::TYPE_HANDOVER_READY => [
            'USER' => HandoverReadyForFamilyStrategy::class,
            'VOLUNTEER' => HandoverReadyForFamilyStrategy::class,
            'THIRD_PARTY_VERIFIER' => HandoverReadyForVerifierStrategy::class,
        ],
        NotificationEvent::TYPE_ROLE_CHANGED => [
            'USER' => RoleChangedNotification::class,
            'VOLUNTEER' => RoleChangedNotification::class,
            'THIRD_PARTY_VERIFIER' => RoleChangedNotification::class,
            'MODERATOR' => RoleChangedNotification::class,
            'ADMIN' => RoleChangedNotification::class,
        ],
    ],
];
