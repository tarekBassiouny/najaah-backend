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

uses(RefreshDatabase::class)->group('view-limits', 'playback');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function makeViewDevice(User $user, string $uuid = 'device-123')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Test Device',
        'device_os' => 'Test OS',
    ]);
}

function attachCourseVideo(): array
{
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
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

it('blocks playback when full plays reach limit', function (): void {
    [$course, $video] = attachCourseVideo();
    $device = makeViewDevice($this->apiUser);

    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->apiUser->studentSetting()->create(['settings' => ['view_limit' => 1]]);

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'progress_percent' => 100,
        'ended_at' => now(),
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VIEW_LIMIT_EXCEEDED',
                'message' => 'View limit exceeded.',
            ],
        ], 403)));
    app()->instance(PlaybackAuthorizationService::class, $mock);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'VIEW_LIMIT_EXCEEDED');
});

it('allows playback when only partial plays exist', function (): void {
    [$course, $video] = attachCourseVideo();
    $device = makeViewDevice($this->apiUser);

    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->apiUser->studentSetting()->create(['settings' => ['view_limit' => 1]]);

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => false,
        'progress_percent' => 80,
        'ended_at' => now(),
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andReturn([
            'embed_config' => [
                'video_id' => 'bunny-1',
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

it('extends limit using extra view allowance', function (): void {
    [$course, $video] = attachCourseVideo();
    $device = makeViewDevice($this->apiUser);

    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->apiUser->studentSetting()->create([
        'settings' => [
            'view_limit' => 1,
            'extra_views' => [
                $video->id => 2,
            ],
        ],
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'progress_percent' => 100,
        'ended_at' => now(),
    ]);

    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andReturn([
            'embed_config' => [
                'video_id' => 'bunny-2',
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
