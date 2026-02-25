<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://localhost:5174',
        'http://31.97.176.48:8081',
        'http://31.97.176.48',
        env('FRONTEND_URL'),
    ]),

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.ngrok-free\.app$/',
        '/^https:\/\/.*\.ngrok\.io$/',
        '/^https:\/\/.*\.ngrok\.app$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
