<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\MediaSourceType;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\DomainException;
use App\Exceptions\UploadFailedException;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Audit\AuditLogService;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Centers\CenterScopeService;
use App\Services\Videos\Contracts\VideoUploadServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\Log;

class VideoUploadService implements VideoUploadServiceInterface
{
    public function __construct(
        private readonly BunnyStreamService $bunnyService,
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
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

        $uploadTtl = (int) config('uploads.video_upload_token_ttl_seconds', 10800);
        $created = $this->bunnyService->createVideo($payload, $libraryIdValue, $uploadTtl);
        $bunnyId = $created['id'];

        $session = VideoUploadSession::create([
            'center_id' => $center->id,
            'uploaded_by' => $admin->id,
            'library_id' => $libraryIdValue,
            'bunny_upload_id' => $bunnyId,
            'upload_status' => VideoUploadStatus::Pending,
            'progress_percent' => 0,
            'expires_at' => now()->addSeconds($uploadTtl),
        ]);

        if ($video !== null) {
            $video->upload_session_id = $session->id;
            $video->original_filename = $originalFilename;
            $video->source_provider = $video->source_provider ?: 'bunny';
            $video->source_type = $video->source_type ?: MediaSourceType::Upload;
            $video->source_id = $bunnyId;
            $video->library_id = $video->library_id ?? $libraryIdValue;
            $this->applyVideoState($video, VideoUploadStatus::Pending, []);
        }

        // Store both legacy upload URL and new TUS presigned data
        $session->setAttribute('upload_url', $created['upload_url']);
        $session->setAttribute('tus_upload_url', $created['tus_upload_url']);
        $session->setAttribute('presigned_headers', $created['presigned_headers']);

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

        $this->auditLogService->log($admin, $session, AuditActions::VIDEO_UPLOAD_SESSION_CREATED, [
            'center_id' => $center->id,
            'video_id' => $video?->id,
            'retry_from_session_id' => $previousSessionId,
        ]);

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
            throw new UploadFailedException('Upload session expired.', 422);
        }

        $status = $this->statusFromLabel($statusLabel);
        $currentStatus = $session->upload_status instanceof VideoUploadStatus
            ? $session->upload_status
            : VideoUploadStatus::from($session->upload_status);

        if (! $currentStatus->canTransitionTo($status)) {
            throw new DomainException('Invalid status transition.', ErrorCodes::INVALID_STATE, 422);
        }

        $session->upload_status = $status;
        $session->progress_percent = isset($payload['progress_percent']) ? max(0, min(100, (int) $payload['progress_percent'])) : $session->progress_percent;
        $session->error_message = $payload['error_message'] ?? null;

        if ($status === VideoUploadStatus::Ready) {
            $session->progress_percent = 100;
            $session->error_message = null;
        }

        $session->save();

        $this->auditLogService->log($admin, $session, AuditActions::VIDEO_UPLOAD_SESSION_TRANSITIONED, [
            'center_id' => $session->center_id,
            'status' => match ($status) {
                VideoUploadStatus::Pending => 0,
                VideoUploadStatus::Uploading => 1,
                VideoUploadStatus::Processing => 2,
                VideoUploadStatus::Ready => 3,
                VideoUploadStatus::Failed => 4,
            },
        ]);

        if ($status === VideoUploadStatus::Ready) {
            Log::channel('domain')->info('video_upload_session_ready', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);
        }

        if ($status === VideoUploadStatus::Failed) {
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

    private function statusFromLabel(string $label): VideoUploadStatus
    {
        try {
            return VideoUploadStatus::fromLabel($label);
        } catch (\ValueError) {
            throw new DomainException('Invalid status label.', ErrorCodes::INVALID_STATE, 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyVideoState(Video $video, VideoUploadStatus $status, array $payload, ?VideoUploadSession $session = null): void
    {
        if ($session !== null && $video->upload_session_id !== null && $video->upload_session_id !== $session->id && $status === VideoUploadStatus::Ready) {
            throw new DomainException('Only the latest upload session can mark the video as ready.', ErrorCodes::INVALID_STATE, 422);
        }

        $video->encoding_status = $status === VideoUploadStatus::Failed
            ? VideoUploadStatus::Pending
            : $status;
        $video->lifecycle_status = match ($status) {
            VideoUploadStatus::Pending => VideoLifecycleStatus::Pending,
            VideoUploadStatus::Uploading => VideoLifecycleStatus::Processing,
            VideoUploadStatus::Processing => VideoLifecycleStatus::Processing,
            VideoUploadStatus::Ready => VideoLifecycleStatus::Ready,
            VideoUploadStatus::Failed => VideoLifecycleStatus::Pending,
        };

        if ($status === VideoUploadStatus::Ready) {
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
