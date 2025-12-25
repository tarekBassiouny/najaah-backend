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
use Tests\Helpers\ApiTestHelper;
use Tests\Helpers\EnrollmentTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class, EnrollmentTestHelper::class)
    ->group('mobile', 'playback');

beforeEach(function (): void {
    config([
        'services.system_api_key' => 'system-key',
        'bunny.api.api_key' => 'bunny-secret',
    ]);
});

test('it creates playback session when views remain', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertOk()
        ->assertJsonPath('data.library_id', (string) $video->library_id)
        ->assertJsonPath('data.video_uuid', 'video-uuid')
        ->assertJsonPath('data.session_id', fn ($value) => is_string($value) && $value !== '')
        ->assertJsonPath('data.embed_token', fn ($value) => is_string($value) && $value !== '');

    $this->assertDatabaseHas('playback_sessions', [
        'user_id' => $student->id,
        'video_id' => $video->id,
    ]);
});

test('it rejects playback without enrollment', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(403)->assertJsonPath('error.code', 'ENROLLMENT_REQUIRED');
});

test('it rejects playback when views are exhausted', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext(['default_view_limit' => 1]);
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $device = UserDevice::where('user_id', $student->id)->where('status', UserDevice::STATUS_ACTIVE)->first();
    expect($device)->not->toBeNull();
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'progress_percent' => 100,
        'ended_at' => now(),
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(403)->assertJsonPath('error.code', 'VIEW_LIMIT_EXCEEDED');
});

test('it rejects multiple active playback sessions', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $device = UserDevice::where('user_id', $student->id)->where('status', UserDevice::STATUS_ACTIVE)->first();
    expect($device)->not->toBeNull();
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => false,
        'progress_percent' => 0,
        'ended_at' => null,
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(409)->assertJsonPath('error.code', 'ACTIVE_SESSION_EXISTS');
});

test('it updates progress and counts view once after 50 percent', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");
    $sessionId = $response->json('data.session_id');

    $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => (int) $sessionId,
        'percentage' => 20,
    ])->assertOk();

    $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => (int) $sessionId,
        'percentage' => 60,
    ])->assertOk();

    $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => (int) $sessionId,
        'percentage' => 80,
    ])->assertOk();

    $session = PlaybackSession::query()->find((int) $sessionId);
    expect($session?->progress_percent)->toBe(80)
        ->and($session?->is_full_play)->toBeTrue();

    $fullPlays = PlaybackSession::where('user_id', $student->id)
        ->where('video_id', $video->id)
        ->where('is_full_play', true)
        ->count();

    expect($fullPlays)->toBe(1);
});

test('it rejects invalid playback session', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => 999999,
        'percentage' => 10,
    ]);

    $response->assertStatus(404)->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
});

test('it allows system student playback for unbranded center', function (): void {
    $center = Center::factory()->create(['type' => 0, 'default_view_limit' => 2]);
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
        'source_id' => 'video-uuid-system',
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

    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertOk()->assertJsonPath('data.video_uuid', 'video-uuid-system');
});

test('it blocks system student playback for branded center', function (): void {
    $center = Center::factory()->create(['type' => 1, 'default_view_limit' => 2]);
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
        'source_id' => 'video-uuid-branded',
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

    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('it blocks branded student playback for another center', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $otherCenter = Center::factory()->create(['type' => 1]);
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$otherCenter->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('it rejects playback when course is not published', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $course->update(['status' => 1, 'is_published' => false]);

    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(404)->assertJsonPath('error.code', 'NOT_FOUND');
});

test('it rejects playback when video is not ready', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $video->update(['encoding_status' => 1, 'lifecycle_status' => 1]);

    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(422)->assertJsonPath('error.code', 'VIDEO_NOT_READY');
});

test('it rejects playback when upload session is not ready', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $video->uploadSession?->update(['upload_status' => 1]);
    $video->refresh();

    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(422)->assertJsonPath('error.code', 'VIDEO_NOT_READY');
});

test('it rejects playback without authentication', function (): void {
    [$student, $center, $course, $video] = buildPlaybackContext();
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->postJson("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertStatus(401)->assertJsonPath('error.code', 'INVALID_API_KEY');
});

/**
 * @return array{0:User,1:Center,2:Course,3:Video}
 */
function buildPlaybackContext(array $centerOverrides = []): array
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
