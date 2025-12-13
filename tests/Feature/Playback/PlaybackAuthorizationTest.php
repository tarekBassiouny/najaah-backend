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

uses(RefreshDatabase::class)->group('playback', 'api');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function makeApprovedDevice(User $user, string $uuid = 'device-123')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Test Device',
        'device_os' => 'Test OS',
    ]);
}

function attachVideoToCourse(Course $course): Video
{
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

    return $video;
}

it('authorizes playback when all rules pass', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student);
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['playback_url', 'session_id', 'expires_at']]);
});

it('blocks playback without enrollment', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student);
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('blocks playback when enrollment inactive', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student);
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_DEACTIVATED,
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('blocks playback for unapproved device', function (): void {
    $student = $this->apiUser;
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'unknown-device',
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('blocks playback when device does not match active binding', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student, 'device-1');
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-2',
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('blocks playback when view limit exceeded', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student);
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    $student->studentSetting()->create(['settings' => ['view_limit' => 1]]);
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'progress_percent' => 100,
        'started_at' => now()->subMinutes(10),
        'ended_at' => now()->subMinutes(5),
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('blocks concurrent playback sessions', function (): void {
    $student = $this->apiUser;
    $device = makeApprovedDevice($student);
    $course = Course::factory()->create(['status' => 3, 'is_published' => true]);
    $video = attachVideoToCourse($course);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinutes(2),
        'ended_at' => null,
        'is_full_play' => false,
    ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $response->assertStatus(409)->assertJsonPath('success', false);
});
