<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Video;
use App\Services\Bunny\BunnyStreamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchBunnyVideoMetadataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        public readonly string $videoGuid,
        public readonly int $libraryId,
        public readonly int $centerId
    ) {
        $this->onQueue((string) config('bunny.metadata_queue', 'bunny'));
    }

    public function handle(BunnyStreamService $bunnyStreamService): void
    {
        $payload = $bunnyStreamService->getVideo($this->videoGuid, $this->libraryId);
        $durationSeconds = $this->resolveDurationSeconds($payload);
        $shouldRetryForDuration = $this->shouldRetryForDuration($payload, $durationSeconds);

        Log::channel('domain')->info('bunny_video_metadata_response', [
            'video_guid' => $this->videoGuid,
            'library_id' => $this->libraryId,
            'center_id' => $this->centerId,
            'payload' => $payload,
        ]);

        Log::channel('domain')->info('bunny_thumbnail_metadata', [
            'video_guid' => $this->videoGuid,
            'library_id' => $this->libraryId,
            'has_thumbnail_url' => is_string($payload['thumbnail_url'] ?? null) && ($payload['thumbnail_url'] ?? '') !== '',
            'has_thumbnailUrl' => is_string($payload['thumbnailUrl'] ?? null) && ($payload['thumbnailUrl'] ?? '') !== '',
            'thumbnail_file_name' => $payload['thumbnailFileName'] ?? $payload['thumbnail_file_name'] ?? null,
            'duration_seconds' => $durationSeconds,
            'should_retry_for_duration' => $shouldRetryForDuration,
        ]);

        $thumbnailUrl = $this->resolveThumbnailUrl($payload);
        if ($thumbnailUrl === null && $durationSeconds === null) {
            return;
        }

        $updates = [];
        if ($thumbnailUrl !== null) {
            $updates['thumbnail_url'] = $thumbnailUrl;
        }

        if ($durationSeconds !== null) {
            $updates['duration_seconds'] = $durationSeconds;
        }

        $updated = Video::query()
            ->where('source_id', $this->videoGuid)
            ->where('library_id', $this->libraryId)
            ->where('center_id', $this->centerId)
            ->update($updates);

        Log::channel('domain')->info('bunny_video_metadata_saved', [
            'video_guid' => $this->videoGuid,
            'library_id' => $this->libraryId,
            'center_id' => $this->centerId,
            'updated_rows' => $updated,
            'updates' => $updates,
        ]);

        if ($shouldRetryForDuration) {
            throw new \RuntimeException('Bunny metadata duration is not ready yet.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveThumbnailUrl(array $payload): ?string
    {
        $candidates = [
            $payload['thumbnail_url'] ?? null,
            $payload['thumbnailUrl'] ?? null,
            $payload['preview_thumbnail_url'] ?? null,
            $payload['previewThumbnailUrl'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        $thumbnailFileName = $payload['thumbnailFileName'] ?? $payload['thumbnail_file_name'] ?? null;
        if (! is_string($thumbnailFileName) || $thumbnailFileName === '') {
            Log::channel('domain')->info('bunny_thumbnail_missing', [
                'video_guid' => $this->videoGuid,
                'library_id' => $this->libraryId,
                'reason' => 'thumbnail_file_name_missing',
            ]);

            return null;
        }

        if (str_starts_with($thumbnailFileName, 'http://') || str_starts_with($thumbnailFileName, 'https://')) {
            return $thumbnailFileName;
        }

        $baseUrl = config('bunny.thumbnail_base_url');
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            Log::channel('domain')->info('bunny_thumbnail_missing', [
                'video_guid' => $this->videoGuid,
                'library_id' => $this->libraryId,
                'reason' => 'thumbnail_base_url_missing',
                'thumbnail_file_name' => $thumbnailFileName,
            ]);

            return null;
        }

        return rtrim($baseUrl, '/').'/'.trim($this->videoGuid, '/').'/'.ltrim($thumbnailFileName, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveDurationSeconds(array $payload): ?int
    {
        $candidates = [
            $payload['duration_seconds'] ?? null,
            $payload['durationSeconds'] ?? null,
            $payload['duration'] ?? null,
            $payload['length'] ?? null,
            $payload['Length'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_int($candidate) && $candidate > 0) {
                return $candidate;
            }

            if (is_float($candidate) && $candidate > 0) {
                return (int) round($candidate);
            }

            if (is_string($candidate) && is_numeric($candidate) && (float) $candidate > 0) {
                return (int) round((float) $candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldRetryForDuration(array $payload, ?int $durationSeconds): bool
    {
        if ($durationSeconds !== null) {
            return false;
        }

        $status = $payload['status'] ?? $payload['Status'] ?? null;
        if (is_numeric($status)) {
            $statusValue = (int) $status;
            if (in_array($statusValue, [0, 1, 2, 6, 7], true)) {
                return true;
            }
        }

        $encodeProgress = $payload['encodeProgress'] ?? $payload['EncodeProgress'] ?? null;
        if (is_numeric($encodeProgress) && (int) $encodeProgress < 100) {
            return true;
        }

        return false;
    }
}
