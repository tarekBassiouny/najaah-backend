<?php

declare(strict_types=1);

return [
    'session_ttl' => (int) env('PLAYBACK_SESSION_TTL', 600),
    'full_play_threshold' => (int) env('PLAYBACK_FULL_PLAY_THRESHOLD', 80),
    'session_timeout_seconds' => (int) env('PLAYBACK_SESSION_TIMEOUT', 60),
    'embed_token_ttl_min' => (int) env('PLAYBACK_TOKEN_TTL_MIN', 180),
    'embed_token_ttl_max' => (int) env('PLAYBACK_TOKEN_TTL_MAX', 300),
];
