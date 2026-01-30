<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Helpers\ApiTestHelper;
use Tests\Helpers\EnrollmentTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class, EnrollmentTestHelper::class)
    ->group('mobile', 'playback', 'refresh-token');

beforeEach(function (): void {
    config([
        'services.system_api_key' => 'system-key',
        'bunny.api.api_key' => 'bunny-secret',
        'bunny.api.library_id' => 55,
        'bunny.embed_key' => 'test-embed-secret-key',
        'bunny.embed_token_ttl' => 240,
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('it refreshes playback token for active session', function (): void {
    Carbon::setTestNow('2024-01-01 00:00:00');

    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $requestResponse = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback"
    );
    $sessionId = $requestResponse->json('data.session_id');
    $firstEmbedUrl = $requestResponse->json('data.embed_url');

    Carbon::setTestNow('2024-01-01 00:05:00');

    $refreshResponse = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $sessionId]
    );

    $refreshResponse->assertOk()
        ->assertJsonPath('data.session_id', $sessionId)
        ->assertJsonPath('data.embed_url', fn ($value) => is_string($value) && str_contains($value, 'iframe.mediadelivery.net'))
        ->assertJsonPath('data.session_expires_in', fn ($value) => is_int($value) && $value > 0)
        ->assertJsonPath('data.session_expires_at', fn ($value) => is_string($value) && ! empty($value));

    // Verify the embed URL changed (new token)
    $newEmbedUrl = $refreshResponse->json('data.embed_url');
    expect($newEmbedUrl)->not->toBe($firstEmbedUrl);
});

test('it returns not found for invalid session id', function (): void {
    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => 999999]
    );

    $response->assertStatus(404)->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
});

test('it rejects refresh when session belongs to another user', function (): void {
    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);

    $otherStudent = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);
    $session = createActiveSession($otherStudent, $video);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $session->id]
    );

    $response->assertStatus(403)->assertJsonPath('error.code', 'UNAUTHORIZED');
});

test('it rejects refresh when session has ended', function (): void {
    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);

    $session = createActiveSession($student, $video);
    $session->update(['ended_at' => now()]);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $session->id]
    );

    $response->assertStatus(409)->assertJsonPath('error.code', 'SESSION_ENDED');
});

test('it rejects refresh when center mismatches student', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $otherCenter = Center::factory()->create(['type' => 1]);

    $course = Course::factory()->create([
        'center_id' => $otherCenter->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $upload = VideoUploadSession::factory()->create([
        'center_id' => $otherCenter->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'video-uuid-center-mismatch',
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

    $this->asApiUser($student);
    $session = createActiveSession($student, $video);

    $response = $this->apiPost(
        "/api/v1/centers/{$otherCenter->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $session->id]
    );

    $response->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('it rejects refresh when enrollment is inactive', function (): void {
    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);

    $session = createActiveSession($student, $video);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $session->id]
    );

    $response->assertStatus(403)->assertJsonPath('error.code', 'ENROLLMENT_REQUIRED');
});

test('it rejects refresh when video is not ready', function (): void {
    [$student, $center, $course, $video] = buildRefreshPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $video->update(['encoding_status' => 1, 'lifecycle_status' => 1]);

    $session = createActiveSession($student, $video);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/refresh_token",
        ['session_id' => $session->id]
    );

    $response->assertStatus(422)->assertJsonPath('error.code', 'VIDEO_NOT_READY');
});

/**
 * @return array{0:User,1:Center,2:Course,3:Video}
 */
function buildRefreshPlaybackContext(array $centerOverrides = []): array
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

    return [$student, $center, $course, $video];
}

function createActiveSession(User $student, Video $video): PlaybackSession
{
    $device = UserDevice::query()
        ->where('user_id', $student->id)
        ->where('status', UserDevice::STATUS_ACTIVE)
        ->first();

    if ($device === null) {
        $device = UserDevice::factory()->create([
            'user_id' => $student->id,
            'status' => UserDevice::STATUS_ACTIVE,
        ]);
    }

    return PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => null,
        'expires_at' => now()->addMinutes(10),
        'progress_percent' => 0,
        'is_full_play' => false,
    ]);
}
