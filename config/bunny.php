<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Bunny driver per environment
    |--------------------------------------------------------------------------
    |
    | Choose which Bunny client implementation to use. The driver can be
    | overridden via BUNNY_DRIVER, otherwise it is selected based on APP_ENV.
    |
    | Supported: "api", "fake"
    */
    'driver' => env('BUNNY_DRIVER'),

    'environments' => [
        'production' => 'api',
        'staging' => 'api',
        'dev' => 'api',
        'local' => 'fake',
        'testing' => 'fake',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bunny API configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'api_url' => env('BUNNY_API_URL', 'https://video.bunnycdn.com'),
        'api_key' => env('BUNNY_API_KEY'),
        'library_id' => env('BUNNY_LIBRARY_ID'),
        'pull_zone' => env('BUNNY_PULL_ZONE'),
        'drm_enabled' => (bool) env('BUNNY_DRM_ENABLED', false),
        'webhook_secret' => env('BUNNY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fake client configuration (local & tests)
    |--------------------------------------------------------------------------
    */
    'fake' => [
        'storage_path' => storage_path('app/private/bunny-fake'),
    ],
];
