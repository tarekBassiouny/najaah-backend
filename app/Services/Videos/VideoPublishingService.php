<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Exceptions\PublishBlockedException;
use App\Models\Video;
use Illuminate\Support\Facades\Log;

class VideoPublishingService
{
    public function ensurePublishable(Video $video): void
    {
        if ((int) $video->encoding_status !== VideoUploadService::STATUS_READY || (int) $video->lifecycle_status < 2) {
            throw new PublishBlockedException('Video is not ready for publishing.', 422);
        }

        if ($video->upload_session_id === null) {
            throw new PublishBlockedException('Video upload session is required.', 422);
        }

        $session = $video->uploadSession()->first();

        if ($session !== null && (int) $session->upload_status !== VideoUploadService::STATUS_READY) {
            throw new PublishBlockedException('Latest upload session is not ready.', 422);
        }

        if ($session !== null && $session->expires_at !== null && $session->expires_at->isPast()) {
            Log::channel('domain')->warning('upload_session_expired', [
                'video_id' => $video->id,
                'session_id' => $session->id,
            ]);
            throw new PublishBlockedException('Latest upload session has expired.', 422);
        }
    }
}
