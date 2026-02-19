<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Pivots\CourseVideo;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('extra-view-requests', 'mobile');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'device-123');
});

function buildExtraViewRequestContext(int $defaultViewLimit): array
{
    $center = Center::factory()->create([
        'default_view_limit' => $defaultViewLimit,
    ]);

    $course = Course::factory()->create([
        'status' => 3,
        'is_published' => true,
        'center_id' => $center->id,
    ]);

    /** @var Video $video */
    $video = Video::factory()->create([
        'lifecycle_status' => 2,
        'encoding_status' => 3,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    return [$center, $course, $video];
}

it('creates extra view request when views are exhausted', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(0);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view", [
        'reason' => 'Need another attempt',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('extra_view_requests', [
        'user_id' => $student->id,
        'video_id' => $video->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
});

it('blocks extra view request when views are still available', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(1);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view");

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VIEWS_AVAILABLE');
});

it('blocks extra view request when center disables extra view requests', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(0);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'allow_extra_view_requests' => false,
        ],
    ]);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

it('blocks duplicate pending extra view requests', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(0);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view");

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('blocks extra view request without enrollment', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(0);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'ENROLLMENT_REQUIRED');
});

it('blocks system students from requesting extra views in branded centers', function (): void {
    [$center, $course, $video] = buildExtraViewRequestContext(0);
    $center->update(['type' => 1]);

    $student = $this->apiUser;
    $student->center_id = null;
    $student->save();
    $this->asApiUser($student);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/extra-view",
        [],
        ['X-Api-Key' => (string) config('services.system_api_key')]
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});
