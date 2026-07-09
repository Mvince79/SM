<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ── Google OAuth — dùng GOOGLE_* trong .env ─────────────────────────────
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/auth/google/callback'),
    ],

    'ekyc' => [
        'provider' => env('EKYC_PROVIDER', 'tesseract'), // fpt_ai | vnpt | tesseract
        'queue' => env('EKYC_QUEUE', 'kyc'),
        'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),

        'fpt_ai' => [
            'base_url' => env('FPT_AI_BASE_URL', 'https://api.fpt.ai'),
            'api_key' => env('FPT_AI_API_KEY'),
        ],

        'vnpt' => [
            'base_url' => env('VNPT_EKYC_BASE_URL'),
            'token_id' => env('VNPT_EKYC_TOKEN_ID'),
            'token_key' => env('VNPT_EKYC_TOKEN_KEY'),
        ],
    ],

];