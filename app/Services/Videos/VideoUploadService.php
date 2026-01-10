<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Centers\CenterScopeService;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\Log;

class VideoUploadService
{
    public const STATUS_PENDING = 0;

    public const STATUS_UPLOADING = 1;

    public const STATUS_PROCESSING = 2;

    public const STATUS_READY = 3;

    public const STATUS_FAILED = 4;

    public function __construct(
        private readonly BunnyStreamService $bunnyService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function initializeUpload(User $admin, Center $center, string $originalFilename, ?Video $video = null): VideoUploadSession
    {
        $this->centerScopeService->assertAdminSameCenter($admin, $center);
        $libraryIdValue = is_numeric(config('bunny.api.library_id')) ? (int) config('bunny.api.library_id') : null;

        if ($libraryIdValue === null) {
            throw new DomainException('Bunny library is not configured.', ErrorCodes::INVALID_STATE, 422);
        }

        $previousSessionId = null;

        if ($video !== null) {
            $video->loadMissing('creator');
            $this->centerScopeService->assertAdminCenterId($admin, $video->creator->center_id);
            $previousSessionId = $video->upload_session_id;
        }

        $courseId = $this->resolveCourseId($video);
        $title = $this->resolveTitle($center->id, $courseId, $video, $originalFilename);
        $payload = [
            'title' => $title,
            'meta' => [
                'center_id' => $center->id,
                'course_id' => $courseId,
                'env' => (string) config('app.env'),
            ],
        ];
        $created = $this->bunnyService->createVideo($payload, $libraryIdValue);
        $bunnyId = $created['id'];

        $session = VideoUploadSession::create([
            'center_id' => $center->id,
            'uploaded_by' => $admin->id,
            'library_id' => $libraryIdValue,
            'bunny_upload_id' => $bunnyId,
            'upload_status' => self::STATUS_PENDING,
            'progress_percent' => 0,
            'expires_at' => now()->addSeconds((int) config('uploads.video_upload_token_ttl_seconds', 3600)),
        ]);

        if ($video !== null) {
            $video->upload_session_id = $session->id;
            $video->original_filename = $originalFilename;
            $video->source_provider = $video->source_provider ?: 'bunny';
            $video->source_type = $video->source_type ?: 1;
            $video->source_id = $bunnyId;
            $video->library_id = $video->library_id ?? $libraryIdValue;
            $this->applyVideoState($video, self::STATUS_PENDING, []);
        }

        $session->setAttribute('upload_url', $created['upload_url']);

        Log::channel('domain')->info('video_upload_session_created', [
            'session_id' => $session->id,
            'center_id' => $center->id,
            'video_id' => $video?->id,
            'retry_from_session_id' => $previousSessionId,
        ]);

        if ($previousSessionId !== null) {
            Log::channel('domain')->info('video_upload_session_retry_created', [
                'session_id' => $session->id,
                'center_id' => $center->id,
                'video_id' => $video?->id,
                'retry_from_session_id' => $previousSessionId,
            ]);
        }

        return $session;
    }

    private function resolveCourseId(?Video $video): ?int
    {
        if (! $video instanceof Video) {
            return null;
        }

        $courseId = $video->courses()->value('courses.id');

        return is_numeric($courseId) ? (int) $courseId : null;
    }

    private function resolveTitle(int $centerId, ?int $courseId, ?Video $video, string $originalFilename): string
    {
        if ($video instanceof Video && $video->section_id !== null && is_numeric($courseId)) {
            return sprintf(
                'center_%d/course_%d/section_%d/video_%d/%s',
                $centerId,
                $courseId,
                $video->section_id,
                $video->id,
                $originalFilename
            );
        }

        return sprintf(
            'center_%d_course_%d_video_%d_%s',
            $centerId,
            $courseId,
            $video->id ?? 0,
            $originalFilename
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transition(User $admin, VideoUploadSession $session, string $statusLabel, array $payload): VideoUploadSession
    {
        $this->centerScopeService->assertAdminSameCenter($admin, $session);

        if ($session->expires_at !== null && $session->expires_at->isPast()) {
            throw new \App\Exceptions\UploadFailedException('Upload session expired.', 422);
        }

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

        if ($status === self::STATUS_READY) {
            Log::channel('domain')->info('video_upload_session_ready', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);
        }

        if ($status === self::STATUS_FAILED) {
            Log::channel('domain')->warning('video_upload_session_failed', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);
        }

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
            throw new DomainException('Invalid status label.', ErrorCodes::INVALID_STATE, 422);
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
            throw new DomainException('Invalid status transition.', ErrorCodes::INVALID_STATE, 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyVideoState(Video $video, int $status, array $payload, ?VideoUploadSession $session = null): void
    {
        if ($session !== null && $video->upload_session_id !== null && $video->upload_session_id !== $session->id && $status === self::STATUS_READY) {
            throw new DomainException('Only the latest upload session can mark the video as ready.', ErrorCodes::INVALID_STATE, 422);
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
