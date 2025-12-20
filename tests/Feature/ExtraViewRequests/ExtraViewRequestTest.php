<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('extra-view-requests');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function makeRequestDevice(User $user, string $uuid = 'device-123')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Test Device',
        'device_os' => 'Test OS',
    ]);
}

function attachCourseAndVideo(): array
{
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'is_published' => true,
        'center_id' => $center->id,
    ]);
    /** @var Video $video */
    $video = Video::factory()->create([
        'source_url' => 'https://videos.example.com/'.$course->id.'/video.mp4',
        'lifecycle_status' => 2,
        'encoding_status' => 3,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    return [$course, $video];
}

it('creates a request and blocks duplicates', function (): void {
    [$course, $video] = attachCourseAndVideo();
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $first = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests", [
        'reason' => 'Need more views',
    ]);

    $first->assertCreated()->assertJsonPath('data.status', ExtraViewRequest::STATUS_PENDING);

    $second = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");

    $second->assertStatus(422)->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('admin approves and allowance affects view limit', function (): void {
    [$course, $video] = attachCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $course->center_id,
    ]);
    $this->asApiUser($student);
    $device = makeRequestDevice($this->apiUser);

    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->apiUser->studentSetting()->create([
        'settings' => ['view_limit' => 1],
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $this->apiUser->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'progress_percent' => 100,
        'ended_at' => now(),
    ]);

    $block = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);
    $block->assertStatus(403)->assertJsonPath('error.code', 'VIEW_LIMIT_EXCEEDED');

    $request = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");
    $requestId = $request->json('data.id');

    $admin = $this->asAdmin();
    $approve = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/extra-view-requests/{$requestId}/approve", [
        'granted_views' => 1,
    ]);

    $approve->assertOk()->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED);

    $allowed = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $allowed->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissing(['playback_url', 'expires_at', 'api_key', 'token', 'signed_url', 'cdn_url', 'upload_url']);
});

it('admin can reject pending requests', function (): void {
    [$course, $video] = attachCourseAndVideo();
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $request = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");
    $requestId = $request->json('data.id');

    $admin = $this->asAdmin();
    $reject = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/extra-view-requests/{$requestId}/reject", [
        'decision_reason' => 'Not eligible',
    ]);

    $reject->assertOk()
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_REJECTED)
        ->assertJsonPath('data.decision_reason', 'Not eligible');
});

it('students can only see their own requests', function (): void {
    [$course, $video] = attachCourseAndVideo();
    Enrollment::factory()->create([
        'user_id' => $this->apiUser->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");

    $other = User::factory()->create(['is_student' => true, 'password' => 'secret123']);
    $this->asApiUser($other);
    Enrollment::factory()->create([
        'user_id' => $other->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");

    $list = $this->apiGet('/api/v1/extra-view-requests');
    $list->assertOk()->assertJsonCount(1, 'data');
});
