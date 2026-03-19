<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\MediaSourceType;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\PublishBlockedException;
use App\Models\Video;

class VideoPublishingService
{
    public function ensurePublishable(Video $video): void
    {
        if ($video->encoding_status !== VideoUploadStatus::Ready || $video->lifecycle_status !== VideoLifecycleStatus::Ready) {
            throw new PublishBlockedException('Video is not ready for publishing.', 422);
        }

        if ($video->source_type === MediaSourceType::Url) {
            return;
        }

        if ($video->upload_session_id === null) {
            throw new PublishBlockedException('Video upload session is required.', 422);
        }

        $session = $video->uploadSession()->first();

        if ($session !== null && $session->upload_status !== VideoUploadStatus::Ready) {
            throw new PublishBlockedException('Latest upload session is not ready.', 422);
        }
    }
}
