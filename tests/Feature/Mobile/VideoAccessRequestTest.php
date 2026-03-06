<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Video;
use App\Models\VideoAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('video-access', 'mobile');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'video-access-device');
});

/**
 * @return array{0:Center,1:Course,2:Video}
 */
function buildVideoAccessContext(bool $requiresApproval): array
{
    $center = Center::factory()->create();

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'requires_video_approval' => $requiresApproval,
            'video_code_expiry_days' => 30,
            'pdf_download_permission' => false,
            'device_limit' => 1,
        ],
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'requires_video_approval' => null,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    return [$center, $course, $video];
}

test('student creates video access request when approval is enabled', function (): void {
    [$center, $course, $video] = buildVideoAccessContext(true);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student, null, 'video-access-device');

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-request",
        ['reason' => 'Need to review before exam']
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('video_access_requests', [
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'status' => VideoAccessRequestStatus::Pending->value,
    ]);
});

test('student cannot create duplicate pending video access requests', function (): void {
    [$center, $course, $video] = buildVideoAccessContext(true);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student, null, 'video-access-device');

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    VideoAccessRequest::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'status' => VideoAccessRequestStatus::Pending,
    ]);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-request"
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VIDEO_ACCESS_REQUEST_EXISTS');
});

test('student cannot request access when approval is disabled', function (): void {
    [$center, $course, $video] = buildVideoAccessContext(false);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student, null, 'video-access-device');

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-request"
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

test('video access status reflects locked and pending states', function (): void {
    [$center, $course, $video] = buildVideoAccessContext(true);

    $student = $this->apiUser;
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $this->asApiUser($student, null, 'video-access-device');

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $locked = $this->apiGet(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-status"
    );

    $locked->assertOk()
        ->assertJsonPath('data.has_access', false)
        ->assertJsonPath('data.status', 'locked')
        ->assertJsonPath('data.can_request', true);

    $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-request",
        ['reason' => 'Need lesson access']
    )->assertOk();

    $pending = $this->apiGet(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/access-status"
    );

    $pending->assertOk()
        ->assertJsonPath('data.has_access', false)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.can_request', false);

    expect($pending->json('data.pending_request_id'))->toBeInt();
});
