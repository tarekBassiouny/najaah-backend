<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Exceptions\DomainException;
use App\Exceptions\UploadNotReadyException;
use App\Models\Video;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\Log;

class VideoAccessService
{
    public function assertReadyForPlayback(Video $video): void
    {
        if ($video->encoding_status !== VideoUploadStatus::Ready || $video->lifecycle_status !== VideoLifecycleStatus::Ready) {
            throw new DomainException('Video is not ready for playback.', ErrorCodes::VIDEO_NOT_READY, 422);
        }

        $session = $video->uploadSession;
        if ($session !== null && $session->upload_status !== VideoUploadStatus::Ready) {
            throw new DomainException('Video is not ready for playback.', ErrorCodes::VIDEO_NOT_READY, 422);
        }
    }

    public function assertReadyForAttachment(Video $video): void
    {
        if ($video->encoding_status !== VideoUploadStatus::Ready) {
            throw new AttachmentNotAllowedException('Video is not ready to be attached.', 422);
        }

        if ($video->upload_session_id === null) {
            throw new UploadNotReadyException('Video upload session is required.', 422);
        }

        $video->loadMissing('uploadSession');
        $session = $video->uploadSession;

        if ($session === null) {
            throw new UploadNotReadyException('Video upload session is required.', 422);
        }

        if ($session->expires_at !== null && $session->expires_at <= now()) {
            Log::channel('domain')->warning('upload_session_expired', [
                'video_id' => $video->id,
                'session_id' => $session->id,
            ]);
            throw new UploadNotReadyException('Video upload session has expired.', 422);
        }

        if ($session->upload_status !== VideoUploadStatus::Ready) {
            throw new UploadNotReadyException('Video upload session is not ready.', 422);
        }
    }
}
