<?php

declare(strict_types=1);

return [
    'video_ttl_days' => 3,
    'pdf_ttl_days' => 3,

    // TTL for video upload session tokens (3 hours for large video uploads)
    'video_upload_token_ttl_seconds' => (int) env('VIDEO_UPLOAD_TTL', 10800),
];
