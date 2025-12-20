<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Playback\PlaybackAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;

uses(RefreshDatabase::class)->group('concurrency', 'playback');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function makeConcurrencyDevice(User $user, string $uuid): \App\Models\UserDevice
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Device '.$uuid,
        'device_os' => '1.0',
    ]);
}

function attachConcurrencyCourseVideo(): array
{
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    /** @var Video $video */
    $video = Video::factory()->create([
        'source_url' => 'https://videos.example.com/video.mp4',
        'lifecycle_status' => 2,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => auth('api')->id(),
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    return [$course, $video];
}

it('blocks concurrent session on same device', function (): void {
    [$course, $video] = attachConcurrencyCourseVideo();
    $device = makeConcurrencyDevice($this->apiUser, 'device-1');

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinute(),
        'ended_at' => null,
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'CONCURRENT_PLAYBACK',
                'message' => 'Another playback session is active.',
            ],
        ], 403)));
    app()->instance(PlaybackAuthorizationService::class, $mock);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'CONCURRENT_PLAYBACK');
});

it('blocks concurrent session from another device', function (): void {
    [$course, $video] = attachConcurrencyCourseVideo();
    $first = makeConcurrencyDevice($this->apiUser, 'device-1');

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $first->id,
        'started_at' => now()->subMinute(),
        'ended_at' => null,
    ]);

    $first->update(['status' => \App\Models\UserDevice::STATUS_REVOKED]);
    $second = \App\Models\UserDevice::factory()->create([
        'user_id' => $this->apiUser->id,
        'device_id' => 'device-2',
        'status' => \App\Models\UserDevice::STATUS_ACTIVE,
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'CONCURRENT_PLAYBACK',
                'message' => 'Another playback session is active.',
            ],
        ], 403)));
    app()->instance(PlaybackAuthorizationService::class, $mock);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $second->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'CONCURRENT_PLAYBACK');
});

it('allows playback after previous session ends', function (): void {
    [$course, $video] = attachConcurrencyCourseVideo();
    $device = makeConcurrencyDevice($this->apiUser, 'device-1');

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinutes(5),
        'ended_at' => now()->subMinutes(1),
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andReturn([
            'embed_config' => [
                'video_id' => 'bunny-ok',
                'library_id' => 10,
            ],
        ]);
    app()->instance(PlaybackAuthorizationService::class, $mock);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissing(['playback_url', 'expires_at', 'api_key', 'token', 'signed_url', 'cdn_url', 'upload_url']);
});
