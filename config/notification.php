<?php

return [
    'escalation_threshold_minutes' => (int) env('NOTIFICATION_ESCALATION_THRESHOLD_MINUTES', 30),

    'critical_event_types' => [
        'FACE_MATCH_CONFIRMED',
    ],
];