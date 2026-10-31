<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps (landing / embed)
    |--------------------------------------------------------------------------
    |
    | share_url: Tautan bagikan dari Google Maps (mis. maps.app.goo.gl).
    | embed_url: Opsional — tempel nilai "src" dari Bagikan → Sematkan peta
    | jika iframe bawaan tidak memuat lokasi dengan benar.
    |
    */

    'google_maps' => [
        'share_url' => env('GOOGLE_MAPS_SHARE_URL', 'https://maps.app.goo.gl/msV48DCcYuh6V5Er5'),
        'embed_url' => env('GOOGLE_MAPS_EMBED_URL'),
    ],

];
