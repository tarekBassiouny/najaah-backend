<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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
    $block->assertStatus(403);

    $request = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/extra-view-requests");
    $requestId = $request->json('data.id');

    $admin = $this->asAdmin();
    $admin->update(['center_id' => $course->center_id]);
    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);
    $approve = $this->postJson("/admin/extra-view-requests/{$requestId}/approve", [
        'granted_views' => 1,
    ], $this->adminHeaders());

    $approve->assertOk()->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED);

    $allowed = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => $device->device_id,
    ]);

    $allowed->assertOk()->assertJsonPath('success', true);
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
    $admin->update(['center_id' => $course->center_id]);
    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);
    $reject = $this->postJson("/admin/extra-view-requests/{$requestId}/reject", [
        'decision_reason' => 'Not eligible',
    ], $this->adminHeaders());

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
