<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Models\BunnyWebhookLog;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
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

            if ($session->expires_at !== null && $session->expires_at->isPast()) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'session_expired',
                    'session_id' => $session->id,
                    'video_guid' => $videoGuid,
                ]);

                return;
            }

            if (! $this->shouldTransition($session->upload_status, $mappedStatus)) {
                Log::channel('domain')->info('bunny_webhook_ignored', [
                    'reason' => 'duplicate_or_invalid_transition',
                    'session_id' => $session->id,
                    'status' => $mappedStatus,
                ]);

                return;
            }

            $session->upload_status = $mappedStatus;

            if ($mappedStatus === VideoUploadService::STATUS_FAILED) {
                $session->error_message = $errorMessage;
            }

            if ($mappedStatus === VideoUploadService::STATUS_READY) {
                $session->progress_percent = 100;
                $session->error_message = null;
            }

            $session->save();

            Log::channel('domain')->info('bunny_webhook_applied', [
                'session_id' => $session->id,
                'video_guid' => $videoGuid,
                'status' => $mappedStatus,
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

    private function mapStatus(int $status): ?int
    {
        $map = [
            0 => VideoUploadService::STATUS_PROCESSING,
            1 => VideoUploadService::STATUS_PROCESSING,
            2 => VideoUploadService::STATUS_PROCESSING,
            3 => VideoUploadService::STATUS_READY,
            4 => VideoUploadService::STATUS_READY,
            5 => VideoUploadService::STATUS_FAILED,
            6 => VideoUploadService::STATUS_UPLOADING,
            7 => VideoUploadService::STATUS_PROCESSING,
            8 => VideoUploadService::STATUS_FAILED,
        ];

        if (! isset($map[$status])) {
            return null;
        }

        return $map[$status];
    }

    private function shouldTransition(int $current, int $incoming): bool
    {
        if (in_array($current, [VideoUploadService::STATUS_READY, VideoUploadService::STATUS_FAILED], true)) {
            return false;
        }

        $priority = [
            0 => 0,
            VideoUploadService::STATUS_UPLOADING => 1,
            VideoUploadService::STATUS_PROCESSING => 2,
            VideoUploadService::STATUS_READY => 3,
            VideoUploadService::STATUS_FAILED => 4,
        ];

        $currentPriority = $priority[$current] ?? 0;
        $incomingPriority = $priority[$incoming] ?? 0;

        return $incomingPriority > $currentPriority;
    }

    private function applyVideoState(Video $video, int $status): void
    {
        if ($video->encoding_status === VideoUploadService::STATUS_READY) {
            return;
        }

        if ($status === VideoUploadService::STATUS_FAILED) {
            $video->encoding_status = 0;
            $video->lifecycle_status = 0;
            $video->save();

            return;
        }

        $shouldUpdate = $this->shouldTransition($video->encoding_status, $status);

        if (! $shouldUpdate) {
            return;
        }

        $video->encoding_status = $status;
        $video->lifecycle_status = $status === VideoUploadService::STATUS_READY ? 2 : 1;
        $video->save();
    }
}
