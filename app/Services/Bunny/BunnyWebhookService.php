<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Enums\VideoUploadStatus;
use App\Models\BunnyWebhookLog;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Support\Facades\Log;

class BunnyWebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        try {
            Log::channel('domain')->info('bunny_webhook_payload', [
                'payload' => $payload,
            ]);

            $videoGuid = isset($payload['VideoGuid']) && is_string($payload['VideoGuid'])
                ? $payload['VideoGuid']
                : null;

            $libraryIdRaw = $payload['VideoLibraryId'] ?? $payload['LibraryId'] ?? null;
            $libraryId = is_numeric($libraryIdRaw) ? (int) $libraryIdRaw : null;

            $statusCode = isset($payload['Status']) && is_numeric($payload['Status'])
                ? (int) $payload['Status']
                : null;

            BunnyWebhookLog::create([
                'video_guid' => $videoGuid,
                'library_id' => $libraryId,
                'status' => $statusCode,
                'payload' => $payload,
            ]);

            if ($videoGuid === null || $libraryId === null || $statusCode === null) {
                return;
            }

            $mappedStatus = $this->mapStatus($statusCode);

            if ($mappedStatus === null) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'unmapped_status',
                    'status' => $statusCode,
                    'video_guid' => $videoGuid,
                ]);

                return;
            }

            $session = VideoUploadSession::where('bunny_upload_id', $videoGuid)
                ->where('library_id', $libraryId)
                ->latest()
                ->first();

            $errorMessage = isset($payload['ErrorMessage']) && is_string($payload['ErrorMessage'])
                ? $payload['ErrorMessage']
                : null;

            if ($session === null) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'session_not_found',
                    'video_guid' => $videoGuid,
                    'library_id' => $libraryId,
                ]);

                return;
            }

            // Note: We intentionally do NOT check session expiry here.
            // The expires_at field is for the upload URL TTL, not encoding.
            // Video encoding can take many hours (especially for 2+ hour videos),
            // and we must accept webhook updates regardless of upload URL expiry.
            // The shouldTransition() check below handles terminal state protection.

            $currentStatus = $session->upload_status instanceof VideoUploadStatus
                ? $session->upload_status
                : VideoUploadStatus::from((int) $session->upload_status);

            if (! $this->shouldTransition($currentStatus, $mappedStatus)) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'duplicate_or_invalid_transition',
                    'session_id' => $session->id,
                    'status' => $mappedStatus->value,
                ]);

                return;
            }

            $session->upload_status = $mappedStatus;

            if ($mappedStatus === VideoUploadStatus::Failed) {
                $session->error_message = $errorMessage;
            }

            if ($mappedStatus === VideoUploadStatus::Ready) {
                $session->progress_percent = 100;
                $session->error_message = null;
            }

            $session->save();

            Log::channel('domain')->info('bunny_webhook_applied', [
                'session_id' => $session->id,
                'video_guid' => $videoGuid,
                'status' => $mappedStatus->value,
            ]);

            $videos = Video::where('source_id', $videoGuid)
                ->where('library_id', $libraryId)
                ->where('center_id', $session->center_id)
                ->get();

            if ($videos->isEmpty()) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'video_not_found',
                    'session_id' => $session->id,
                    'video_guid' => $videoGuid,
                ]);

                return;
            }

            foreach ($videos as $video) {
                $this->applyVideoState($video, $mappedStatus);
            }
        } catch (\Throwable) {
            // Swallow all exceptions to keep webhook endpoint idempotent and stable
        }
    }

    private function mapStatus(int $status): ?VideoUploadStatus
    {
        $map = [
            0 => VideoUploadStatus::Processing,
            1 => VideoUploadStatus::Processing,
            2 => VideoUploadStatus::Processing,
            3 => VideoUploadStatus::Ready,
            4 => VideoUploadStatus::Ready,
            5 => VideoUploadStatus::Failed,
            6 => VideoUploadStatus::Uploading,
            7 => VideoUploadStatus::Processing,
            8 => VideoUploadStatus::Failed,
        ];

        return $map[$status] ?? null;
    }

    private function shouldTransition(VideoUploadStatus $current, VideoUploadStatus $incoming): bool
    {
        if (in_array($current, [VideoUploadStatus::Ready, VideoUploadStatus::Failed], true)) {
            return false;
        }

        $currentPriority = $this->priority($current);
        $incomingPriority = $this->priority($incoming);

        return $incomingPriority > $currentPriority;
    }

    private function applyVideoState(Video $video, VideoUploadStatus $status): void
    {
        $currentStatus = $video->encoding_status;

        if ($currentStatus === VideoUploadStatus::Ready) {
            return;
        }

        if ($status === VideoUploadStatus::Failed) {
            $video->encoding_status = VideoUploadStatus::Pending;
            $video->lifecycle_status = 0;
            $video->save();

            return;
        }

        $shouldUpdate = $this->shouldTransition($currentStatus, $status);

        if (! $shouldUpdate) {
            return;
        }

        $video->encoding_status = $status;
        $video->lifecycle_status = $status === VideoUploadStatus::Ready ? 2 : 1;
        $video->save();
    }

    private function priority(VideoUploadStatus $status): int
    {
        return match ($status) {
            VideoUploadStatus::Pending => 0,
            VideoUploadStatus::Uploading => 1,
            VideoUploadStatus::Processing => 2,
            VideoUploadStatus::Ready => 3,
            VideoUploadStatus::Failed => 4,
        };
    }
}
