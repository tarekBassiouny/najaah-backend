<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Bunny API configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'api_url' => env('BUNNY_API_URL', 'https://video.bunnycdn.com'),
        'api_key' => env('BUNNY_STREAM_API_KEY'),
        'library_id' => env('BUNNY_LIBRARY_ID'),
    ],
];
