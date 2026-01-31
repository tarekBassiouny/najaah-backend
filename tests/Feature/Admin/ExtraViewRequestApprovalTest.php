<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('extra-view-requests', 'admin');

function attachAdminCourseAndVideo(): array
{
    $center = Center::factory()->create();
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

it('admin approves and allowance affects view limit', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $course->center_id,
    ]);
    $this->asApiUser($student, null, 'device-123');

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $extraRequest = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $requestId = $extraRequest->id;

    $admin = $this->asAdmin();
    $approve = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/extra-view-requests/{$requestId}/approve", [
        'granted_views' => 1,
    ], $this->adminHeaders());

    $approve->assertOk()
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED->value)
        ->assertJsonPath('data.granted_views', 1);
});

it('admin can reject pending requests', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $course->center_id,
    ]);
    $this->asApiUser($student);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $extraRequest = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $requestId = $extraRequest->id;

    $admin = $this->asAdmin();
    $reject = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/extra-view-requests/{$requestId}/reject", [
        'decision_reason' => 'Not eligible',
    ], $this->adminHeaders());

    $reject->assertOk()
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_REJECTED->value)
        ->assertJsonPath('data.decision_reason', 'Not eligible');
});
