<?php

return [
    'service_url' => env('FACE_MATCH_SERVICE_URL', 'http://127.0.0.1:8001'),
    'internal_key' => env('FACE_MATCH_INTERNAL_KEY'),
    'score_threshold' => (float) env('FACE_MATCH_SCORE_THRESHOLD', 0.70),
    'high_confidence_threshold' => (float) env('FACE_MATCH_HIGH_CONFIDENCE_THRESHOLD', 0.85),
    'timeout' => (int) env('FACE_MATCH_TIMEOUT', 30),
    'health_timeout' => (int) env('FACE_MATCH_HEALTH_TIMEOUT', 3),
    'queue' => env('FACE_MATCH_QUEUE', 'face-match'),
    'model' => env('FACE_MATCH_MODEL', 'ArcFace'),
];
