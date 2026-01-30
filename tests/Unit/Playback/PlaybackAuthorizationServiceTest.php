<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Playback\PlaybackAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('playback', 'authorization');

test('it requires active enrollment for playback start', function (): void {
    [$student, $center, $course, $video] = buildAuthorizationPlaybackContext();

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanStartPlayback($student, $center, $course, $video));

    assertHttpError($thrown, 403, 'ENROLLMENT_REQUIRED');
});

test('it rejects playback when center mismatches', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $upload = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);
    $video = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'video-uuid',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $upload->id,
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $student = User::factory()->create([
        'center_id' => null,
        'is_student' => true,
    ]);

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanStartPlayback($student, $center, $course, $video));

    assertHttpError($thrown, 403, 'CENTER_MISMATCH');
});

test('it rejects playback when video is not ready', function (): void {
    [$student, $center, $course, $video] = buildAuthorizationPlaybackContext();
    $video->update(['encoding_status' => 1, 'lifecycle_status' => 1]);

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanStartPlayback($student, $center, $course, $video));

    assertHttpError($thrown, 422, 'VIDEO_NOT_READY');
});

test('it rejects progress update when session is owned by another student', function (): void {
    [$student, $center, $course, $video] = buildAuthorizationPlaybackContext();
    $otherStudent = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    $session = PlaybackSession::factory()->create([
        'user_id' => $otherStudent->id,
        'video_id' => $video->id,
        'ended_at' => null,
    ]);

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanUpdateProgress($student, $center, $course, $video, $session));

    assertHttpError($thrown, 403, 'UNAUTHORIZED');
});

test('it rejects progress update when session has ended', function (): void {
    [$student, $center, $course, $video] = buildAuthorizationPlaybackContext();

    $session = PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'ended_at' => now(),
    ]);

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanUpdateProgress($student, $center, $course, $video, $session));

    assertHttpError($thrown, 409, 'SESSION_ENDED');
});

test('it rejects progress update when session has expired', function (): void {
    [$student, $center, $course, $video] = buildAuthorizationPlaybackContext();

    $session = PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'ended_at' => null,
        'expires_at' => now()->subMinutes(5), // Expired 5 minutes ago
    ]);

    $service = app(PlaybackAuthorizationService::class);

    $thrown = captureException(fn () => $service->assertCanUpdateProgress($student, $center, $course, $video, $session));

    assertHttpError($thrown, 409, 'SESSION_EXPIRED');
});

/**
 * @return array{0:User,1:Center,2:Course,3:Video}
 */
function buildAuthorizationPlaybackContext(array $centerOverrides = []): array
{
    $center = Center::factory()->create(array_merge([
        'type' => 0,
        'default_view_limit' => 2,
    ], $centerOverrides));

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $upload = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'video-uuid',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $upload->id,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    UserDevice::factory()->create([
        'user_id' => $student->id,
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    return [$student, $center, $course, $video];
}

function captureException(callable $callback): ?DomainException
{
    $thrown = null;

    try {
        $callback();
    } catch (DomainException $exception) {
        $thrown = $exception;
    }

    return $thrown;
}

function assertHttpError(?DomainException $exception, int $status, string $code): void
{
    expect($exception)->not->toBeNull();
    expect($exception?->statusCode())->toBe($status)
        ->and($exception?->errorCode())->toBe($code);
}
