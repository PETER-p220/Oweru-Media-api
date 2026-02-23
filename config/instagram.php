<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Instagram API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Instagram Graph API integration.
    | You need to set up a Facebook Developer account and get the necessary
    | credentials to use Instagram's API.
    |
    */

    'enabled' => env('INSTAGRAM_ENABLED', false),

    'app_id' => env('INSTAGRAM_APP_ID'),
    'app_secret' => env('INSTAGRAM_APP_SECRET'),
    'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),

    'instagram_business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),

    'api_version' => env('INSTAGRAM_API_VERSION', 'v18.0'),

    'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN'),

    'media_limits' => [
        'max_photos' => 10,
        'max_video_size' => 50000, // 50MB in KB
        'max_image_size' => 8000,  // 8MB in KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png'],
        'allowed_video_types' => ['mp4', 'mov'],
        'max_caption_length' => 2200,
        'max_hashtags' => 30,
    ],

    'post_types' => [
        'feed' => 'IMAGE',
        'carousel' => 'CAROUSEL',
        'reel' => 'REELS',
    ],

    'webhooks' => [
        'enabled' => env('INSTAGRAM_WEBHOOKS_ENABLED', false),
        'url' => env('INSTAGRAM_WEBHOOK_URL'),
        'events' => [
            'comments',
            'likes',
            'mentions',
            'story_mentions',
        ],
    ],

    'rate_limits' => [
        'posts_per_hour' => 25,
        'comments_per_hour' => 60,
        'likes_per_hour' => 60,
    ],

    'logging' => [
        'enabled' => env('INSTAGRAM_LOGGING_ENABLED', true),
        'channel' => env('INSTAGRAM_LOG_CHANNEL', 'instagram'),
    ],
];
