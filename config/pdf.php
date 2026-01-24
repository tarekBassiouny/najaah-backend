<?php

declare(strict_types=1);

return [
    // TTL for presigned upload URLs (3 hours default for large file uploads)
    'upload_url_ttl' => (int) env('PDF_UPLOAD_URL_TTL', 10800),

    // TTL for presigned download URLs (15 minutes default)
    'download_url_ttl' => (int) env('PDF_DOWNLOAD_URL_TTL', 900),
];
