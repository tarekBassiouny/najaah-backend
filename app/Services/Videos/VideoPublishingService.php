<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Models\Video;
use Illuminate\Validation\ValidationException;

class VideoPublishingService
{
    public function ensurePublishable(Video $video): void
    {
        if ((int) $video->encoding_status !== VideoUploadService::STATUS_READY || (int) $video->lifecycle_status < 2) {
            throw ValidationException::withMessages([
                'videos' => ['Video is not ready for publishing.'],
            ]);
        }

        if ($video->upload_session_id !== null) {
            $session = $video->uploadSession()->first();

            if ($session !== null && (int) $session->upload_status !== VideoUploadService::STATUS_READY) {
                throw ValidationException::withMessages([
                    'videos' => ['Latest upload session is not ready.'],
                ]);
            }
        }
    }
}
