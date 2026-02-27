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
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
    ],
    'embed_key' => env('BUNNY_EMBED_KEY'),
    'embed_token_ttl' => env('BUNNY_EMBED_TOKEN_TTL', 240),
    'thumbnail_base_url' => env('BUNNY_THUMBNAIL_BASE_URL'),
    'metadata_queue' => env('BUNNY_METADATA_QUEUE', 'bunny'),
];
