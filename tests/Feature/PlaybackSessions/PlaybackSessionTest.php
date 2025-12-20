<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('playback', 'sessions');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function makeDevice(User $user, string $uuid = 'device-123')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Test Device',
        'device_os' => 'Test OS',
    ]);
}

function attachVideoWithCourse(): array
{
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    /** @var Video $video */
    $video = Video::factory()->create([
        'source_url' => 'https://videos.example.com/'.$course->id.'/video.mp4',
        'lifecycle_status' => 2,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    return [$course, $video];
}

it('updates progress monotonically and marks full play once', function (): void {
    [$course, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $session = PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'progress_percent' => 50,
        'ended_at' => null,
    ]);

    $response = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 96,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.progress_percent', 96)
        ->assertJsonPath('data.is_full_play', true);

    $again = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 97,
    ]);

    $again->assertOk()
        ->assertJsonPath('data.progress_percent', 97)
        ->assertJsonPath('data.is_full_play', true);
});

it('ignores decreasing progress updates', function (): void {
    [$course, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $session = PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'progress_percent' => 80,
        'is_full_play' => false,
        'ended_at' => null,
    ]);

    $response = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 60,
    ]);

    $response->assertOk()->assertJsonPath('data.progress_percent', 80);
});

it('requires active enrollment for full play detection', function (): void {
    [, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);

    $session = PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'progress_percent' => 80,
        'is_full_play' => false,
        'ended_at' => null,
    ]);

    $response = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 98,
    ]);

    $response->assertOk()->assertJsonPath('data.is_full_play', false);
});

it('ends a session and records final progress', function (): void {
    [$course, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $session = PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'progress_percent' => 70,
        'is_full_play' => false,
        'ended_at' => null,
    ]);

    $response = $this->apiPost("/api/v1/playback/sessions/{$session->id}/end", [
        'progress_percent' => 95,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.progress_percent', 95)
        ->assertJsonPath('data.is_full_play', true);

    $this->assertNotNull($response->json('data.ended_at'));
});

it('blocks progress updates for ended sessions', function (): void {
    [$course, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $session = PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'progress_percent' => 70,
        'ended_at' => now(),
    ]);

    $response = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 90,
    ]);

    $response->assertStatus(409);
});

it('denies updating sessions of another user', function (): void {
    [$course, $video] = attachVideoWithCourse();
    $device = makeDevice($this->apiUser);
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $otherUser = User::factory()->create(['is_student' => true]);
    $session = PlaybackSession::factory()->create([
        'user_id' => $otherUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => null,
    ]);

    $response = $this->apiPatch("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 50,
    ]);

    $response->assertStatus(404);
});

it('requires authentication for session updates', function (): void {
    auth('api')->logout();
    $this->apiBearerToken = null;

    $session = PlaybackSession::factory()->create([
        'ended_at' => null,
    ]);

    $response = $this->patchJson("/api/v1/playback/sessions/{$session->id}", [
        'progress_percent' => 10,
    ]);

    $response->assertStatus(403);
});
