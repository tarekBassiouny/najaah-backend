<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Permission;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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
    $centerId = $course->center_id;
    $approve = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$centerId}/extra-view-requests/{$requestId}/approve", [
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
    $centerId = $course->center_id;
    $reject = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$centerId}/extra-view-requests/{$requestId}/reject", [
        'decision_reason' => 'Not eligible',
    ], $this->adminHeaders());

    $reject->assertOk()
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_REJECTED->value)
        ->assertJsonPath('data.decision_reason', 'Not eligible');
});

it('supports bulk approve in system scope with skipped and failed', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);

    $pending = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $alreadyApproved = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'granted_views' => 1,
    ]);

    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/extra-view-requests/bulk-approve', [
        'request_ids' => [$pending->id, $alreadyApproved->id, 999999],
        'granted_views' => 2,
        'decision_reason' => 'Bulk review',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.approved', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $pending->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'granted_views' => 2,
    ]);
});

it('supports bulk reject in system scope with skipped and failed', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);

    $pending = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $alreadyRejected = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_REJECTED,
    ]);

    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/extra-view-requests/bulk-reject', [
        'request_ids' => [$pending->id, $alreadyRejected->id, 999999],
        'decision_reason' => 'Bulk reject review',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.rejected', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $pending->id,
        'status' => ExtraViewRequest::STATUS_REJECTED,
        'decision_reason' => 'Bulk reject review',
    ]);
});

it('supports bulk approve in center scope', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $centerId = (int) $course->center_id;
    $otherCenter = Center::factory()->create();

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $otherCenter->id,
    ]);

    $pendingInCenter = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $outsideCenter = ExtraViewRequest::create([
        'user_id' => $otherStudent->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $otherCenter->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$centerId}/extra-view-requests/bulk-approve", [
        'request_ids' => [$pendingInCenter->id, $outsideCenter->id],
        'granted_views' => 2,
        'decision_reason' => 'Center bulk approve',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.approved', 1)
        ->assertJsonPath('data.counts.skipped', 0)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $pendingInCenter->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'granted_views' => 2,
    ]);
    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $outsideCenter->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
});

it('supports bulk reject in center scope', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $centerId = (int) $course->center_id;
    $otherCenter = Center::factory()->create();

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $otherCenter->id,
    ]);

    $pendingInCenter = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
    $outsideCenter = ExtraViewRequest::create([
        'user_id' => $otherStudent->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $otherCenter->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$centerId}/extra-view-requests/bulk-reject", [
        'request_ids' => [$pendingInCenter->id, $outsideCenter->id],
        'decision_reason' => 'Center bulk reject',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.rejected', 1)
        ->assertJsonPath('data.counts.skipped', 0)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $pendingInCenter->id,
        'status' => ExtraViewRequest::STATUS_REJECTED,
        'decision_reason' => 'Center bulk reject',
    ]);
    $this->assertDatabaseHas('extra_view_requests', [
        'id' => $outsideCenter->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);
});

it('allows direct extra view grant for student in system scope', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $centerId = (int) $course->center_id;
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/students/{$student->id}/extra-view-grants", [
        'course_id' => $course->id,
        'video_id' => $video->id,
        'granted_views' => 3,
        'reason' => 'Manual grant',
        'decision_reason' => 'Approved by admin',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED->value)
        ->assertJsonPath('data.granted_views', 3)
        ->assertJsonPath('data.user.id', $student->id);

    $this->assertDatabaseHas('extra_view_requests', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'video_id' => $video->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'granted_views' => 3,
        'reason' => 'Manual grant',
        'decision_reason' => 'Approved by admin',
        'decided_by' => $admin->id,
    ]);
});

it('supports bulk direct extra view grants with skipped and failed', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $centerId = (int) $course->center_id;

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    ExtraViewRequest::create([
        'user_id' => $studentB->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);

    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/extra-view-grants/bulk', [
        'student_ids' => [$studentA->id, $studentB->id, 999999],
        'course_id' => $course->id,
        'video_id' => $video->id,
        'granted_views' => 2,
        'reason' => 'Bulk grant',
        'decision_reason' => 'Bulk review',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.granted', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('extra_view_requests', [
        'user_id' => $studentA->id,
        'course_id' => $course->id,
        'video_id' => $video->id,
        'status' => ExtraViewRequest::STATUS_APPROVED,
        'granted_views' => 2,
    ]);
});

it('supports bulk direct extra view grants in center scope', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $centerId = (int) $course->center_id;
    $otherCenter = Center::factory()->create();

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerId,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $otherCenter->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $course->id,
        'center_id' => $centerId,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$centerId}/extra-view-grants/bulk", [
        'student_ids' => [$studentA->id, $studentB->id],
        'course_id' => $course->id,
        'video_id' => $video->id,
        'granted_views' => 1,
        'reason' => 'Center bulk grant',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.granted', 1)
        ->assertJsonPath('data.counts.skipped', 0)
        ->assertJsonPath('data.counts.failed', 1);
});

it('allows centerless non-super admin with permission to approve in system scope', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);

    $pending = ExtraViewRequest::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => ExtraViewRequest::STATUS_PENDING,
    ]);

    $permission = Permission::firstOrCreate(['name' => 'extra_view.manage'], [
        'description' => 'Permission: extra_view.manage',
    ]);
    $role = Role::firstOrCreate(['slug' => 'extra_view_manager'], [
        'name' => 'extra view manager',
        'name_translations' => ['en' => 'extra view manager', 'ar' => 'extra view manager'],
        'description_translations' => ['en' => 'Extra view management role', 'ar' => 'Extra view management role'],
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson("/api/v1/admin/extra-view-requests/{$pending->id}/approve", [
        'granted_views' => 1,
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED->value)
        ->assertJsonPath('data.granted_views', 1);
});

it('allows centerless non-super admin with permission to grant extra views directly', function (): void {
    [$course, $video] = attachAdminCourseAndVideo();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $permission = Permission::firstOrCreate(['name' => 'extra_view.manage'], [
        'description' => 'Permission: extra_view.manage',
    ]);
    $role = Role::firstOrCreate(['slug' => 'extra_view_manager'], [
        'name' => 'extra view manager',
        'name_translations' => ['en' => 'extra view manager', 'ar' => 'extra view manager'],
        'description_translations' => ['en' => 'Extra view management role', 'ar' => 'Extra view management role'],
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson("/api/v1/admin/students/{$student->id}/extra-view-grants", [
        'course_id' => $course->id,
        'video_id' => $video->id,
        'granted_views' => 2,
        'reason' => 'Direct grant',
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ExtraViewRequest::STATUS_APPROVED->value)
        ->assertJsonPath('data.granted_views', 2)
        ->assertJsonPath('data.user.id', $student->id);
});
