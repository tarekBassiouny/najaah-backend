<?php

declare(strict_types=1);

use App\Enums\MediaSourceType;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('videos', 'services');

beforeEach(function (): void {
    config()->set('bunny.api', [
        'api_key' => 'test-key',
        'api_url' => 'https://bunny.test',
        'library_id' => 1,
    ]);
});

it('initializes upload session and updates provided video', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $session = VideoUploadSession::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
    ]);

    $this->mock(BunnyStreamService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('createVideo')
            ->once()
            ->andReturn([
                'id' => 'bunny-vid-123',
                'upload_url' => 'https://upload.example/legacy',
                'tus_upload_url' => 'https://upload.example/tus',
                'presigned_headers' => ['Authorization' => 'Bearer test'],
            ]);
    });

    $service = app(VideoUploadService::class);
    $newSession = $service->initializeUpload($admin, $center, 'lesson.mp4', $video);

    expect($newSession->bunny_upload_id)->toBe('bunny-vid-123');
    expect($video->fresh()?->upload_session_id)->toBe($newSession->id);
    expect($video->fresh()?->encoding_status)->toBe(VideoUploadStatus::Pending);
    expect($video->fresh()?->source_type)->toBe(MediaSourceType::Upload);
});

it('transitions session to ready and updates linked videos', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Pending,
        'progress_percent' => 5,
        'expires_at' => now()->addHour(),
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadStatus::Pending,
        'lifecycle_status' => VideoLifecycleStatus::Pending,
    ]);

    $service = app(VideoUploadService::class);
    $updatedSession = $service->transition($admin, $session, 'READY', [
        'duration_seconds' => 321,
        'source_id' => 'video-source-id',
    ]);

    expect($updatedSession->upload_status)->toBe(VideoUploadStatus::Ready);
    expect((int) $updatedSession->progress_percent)->toBe(100);

    $video = $video->fresh();
    expect($video?->encoding_status)->toBe(VideoUploadStatus::Ready);
    expect($video?->lifecycle_status)->toBe(VideoLifecycleStatus::Ready);
    expect((int) $video?->duration_seconds)->toBe(321);
});

it('throws when status label is invalid during transition', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Pending,
        'expires_at' => now()->addHour(),
    ]);

    $service = app(VideoUploadService::class);
    $service->transition($admin, $session, 'INVALID_STATUS', []);
})->throws(DomainException::class);

it('does not infer an arbitrary course id when a video is attached to multiple courses', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => null,
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
    ]);

    $courseA = Course::factory()->create(['center_id' => $center->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id]);
    $video->courses()->attach([$courseA->id, $courseB->id]);

    $this->mock(BunnyStreamService::class, function (MockInterface $mock) use ($center, $video): void {
        $mock->shouldReceive('createVideo')
            ->once()
            ->withArgs(function (array $payload) use ($center, $video): bool {
                return data_get($payload, 'meta.center_id') === $center->id
                    && data_get($payload, 'meta.course_id') === null
                    && data_get($payload, 'title') === sprintf(
                        'center_%d_course_0_video_%d_lesson.mp4',
                        $center->id,
                        $video->id
                    );
            })
            ->andReturn([
                'id' => 'bunny-shared-vid',
                'upload_url' => 'https://upload.example/legacy',
                'tus_upload_url' => 'https://upload.example/tus',
                'presigned_headers' => ['Authorization' => 'Bearer test'],
            ]);
    });

    $service = app(VideoUploadService::class);
    $session = $service->initializeUpload($admin, $center, 'lesson.mp4', $video);

    expect($session->bunny_upload_id)->toBe('bunny-shared-vid');
});
