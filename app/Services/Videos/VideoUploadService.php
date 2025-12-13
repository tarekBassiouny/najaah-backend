<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Bunny\BunnyStreamClientInterface;
use Illuminate\Validation\ValidationException;

class VideoUploadService
{
    public const STATUS_PENDING = 0;

    public const STATUS_UPLOADING = 1;

    public const STATUS_PROCESSING = 2;

    public const STATUS_READY = 3;

    public const STATUS_FAILED = 4;

    public function __construct(private readonly BunnyStreamClientInterface $bunnyClient) {}

    public function initializeUpload(User $admin, Center $center, string $originalFilename, ?Video $video = null): VideoUploadSession
    {
        $created = $this->bunnyClient->createVideo(['title' => $originalFilename]);
        $bunnyId = $created['id'];

        $session = VideoUploadSession::create([
            'center_id' => $center->id,
            'uploaded_by' => $admin->id,
            'bunny_upload_id' => $bunnyId,
            'upload_status' => self::STATUS_PENDING,
            'progress_percent' => 0,
        ]);

        if ($video !== null) {
            $video->upload_session_id = $session->id;
            $video->original_filename = $originalFilename;
            $video->source_provider = $video->source_provider ?: 'bunny';
            $video->source_type = $video->source_type ?: 1;
            $video->source_id = $bunnyId;
            $this->applyVideoState($video, self::STATUS_PENDING, []);
        }

        $session->setAttribute('upload_url', $created['upload_url']);

        return $session;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transition(VideoUploadSession $session, string $statusLabel, array $payload): VideoUploadSession
    {
        $status = $this->statusFromLabel($statusLabel);
        $this->assertTransitionAllowed($session->upload_status, $status);

        $session->upload_status = $status;
        $session->progress_percent = isset($payload['progress_percent']) ? max(0, min(100, (int) $payload['progress_percent'])) : $session->progress_percent;
        $session->error_message = $payload['error_message'] ?? null;

        if ($status === self::STATUS_READY) {
            $session->progress_percent = 100;
            $session->error_message = null;
        }

        $session->save();

        $session->loadMissing('videos');

        foreach ($session->videos as $video) {
            $this->applyVideoState($video, $status, $payload, $session);
        }

        return $session->fresh() ?? $session;
    }

    private function statusFromLabel(string $label): int
    {
        $map = [
            'PENDING' => self::STATUS_PENDING,
            'UPLOADING' => self::STATUS_UPLOADING,
            'PROCESSING' => self::STATUS_PROCESSING,
            'READY' => self::STATUS_READY,
            'FAILED' => self::STATUS_FAILED,
        ];

        $upper = strtoupper($label);

        if (! array_key_exists($upper, $map)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status label.'],
            ]);
        }

        return $map[$upper];
    }

    private function assertTransitionAllowed(int $current, int $next): void
    {
        $allowed = [
            self::STATUS_PENDING => [self::STATUS_UPLOADING, self::STATUS_PROCESSING, self::STATUS_READY, self::STATUS_FAILED],
            self::STATUS_UPLOADING => [self::STATUS_PROCESSING, self::STATUS_READY, self::STATUS_FAILED],
            self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_FAILED],
            self::STATUS_READY => [],
            self::STATUS_FAILED => [],
        ];

        if (! in_array($next, $allowed[$current] ?? [], true) && $current !== $next) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status transition.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyVideoState(Video $video, int $status, array $payload, ?VideoUploadSession $session = null): void
    {
        if ($session !== null && $video->upload_session_id !== null && $video->upload_session_id !== $session->id && $status === self::STATUS_READY) {
            throw ValidationException::withMessages([
                'upload_session_id' => ['Only the latest upload session can mark the video as ready.'],
            ]);
        }

        $encodingMap = [
            self::STATUS_PENDING => 0,
            self::STATUS_UPLOADING => 1,
            self::STATUS_PROCESSING => 2,
            self::STATUS_READY => 3,
            self::STATUS_FAILED => 0,
        ];

        $lifecycleMap = [
            self::STATUS_PENDING => 0,
            self::STATUS_UPLOADING => 1,
            self::STATUS_PROCESSING => 1,
            self::STATUS_READY => 2,
            self::STATUS_FAILED => 0,
        ];

        $video->encoding_status = $encodingMap[$status];
        $video->lifecycle_status = $lifecycleMap[$status];

        if ($status === self::STATUS_READY) {
            if (isset($payload['source_id']) && is_string($payload['source_id'])) {
                $video->source_id = $payload['source_id'];
            }

            if (isset($payload['source_url']) && is_string($payload['source_url'])) {
                $video->source_url = $payload['source_url'];
            }

            if (isset($payload['duration_seconds'])) {
                $video->duration_seconds = $payload['duration_seconds'];
            }
        }

        $video->save();
    }
}
