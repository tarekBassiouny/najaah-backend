<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\VideoAccessCodeStatus;
use App\Enums\VideoAccessRequestStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('video-access', 'admin');

/**
 * @return array{0:Center,1:Course,2:Video,3:User,4:Enrollment,5:VideoAccessRequest}
 */
function buildPendingVideoAccessRequestContext(): array
{
    $center = Center::factory()->create();

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'requires_video_approval' => true,
            'video_code_expiry_days' => 30,
            'pdf_download_permission' => false,
            'device_limit' => 1,
        ],
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
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

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $request = VideoAccessRequest::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'status' => VideoAccessRequestStatus::Pending,
        'reason' => 'Please approve',
    ]);

    return [$center, $course, $video, $student, $enrollment, $request];
}

test('admin approves request and code is generated automatically', function (): void {
    [$center, $course, $video, $student, $enrollment, $request] = buildPendingVideoAccessRequestContext();

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-requests/{$request->id}/approve",
        [
            'decision_reason' => 'Approved for class attendance',
            'send_whatsapp' => false,
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.request.status', VideoAccessRequestStatus::Approved->value)
        ->assertJsonPath('data.generated_code.status', VideoAccessCodeStatus::Active->value)
        ->assertJsonPath('data.generated_code.whatsapp_sent', false);

    $this->assertDatabaseHas('video_access_requests', [
        'id' => $request->id,
        'status' => VideoAccessRequestStatus::Approved->value,
        'decided_by' => $admin->id,
    ]);

    $this->assertDatabaseHas('video_access_codes', [
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'enrollment_id' => $enrollment->id,
        'status' => VideoAccessCodeStatus::Active->value,
        'video_access_request_id' => $request->id,
    ]);
});

test('admin bulk approves pending requests', function (): void {
    [$center, $course, $video, $student, $enrollment, $first] = buildPendingVideoAccessRequestContext();

    $secondStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $secondEnrollment = Enrollment::factory()->create([
        'user_id' => $secondStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $second = VideoAccessRequest::query()->create([
        'user_id' => $secondStudent->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $secondEnrollment->id,
        'status' => VideoAccessRequestStatus::Pending,
    ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/video-access-requests/bulk-approve",
        [
            'request_ids' => [$first->id, $second->id],
            'decision_reason' => 'Bulk approved',
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.approved', 2)
        ->assertJsonPath('data.codes_generated', 2)
        ->assertJsonPath('data.whatsapp_sent', 0);

    $this->assertDatabaseHas('video_access_requests', [
        'id' => $first->id,
        'status' => VideoAccessRequestStatus::Approved->value,
    ]);
    $this->assertDatabaseHas('video_access_requests', [
        'id' => $second->id,
        'status' => VideoAccessRequestStatus::Approved->value,
    ]);
});
