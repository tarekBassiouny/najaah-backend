<?php

declare(strict_types=1);

use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\ExtraViewRequestStatus;
use App\Models\AuditLog;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use App\Support\AuditActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('dashboard', 'admin');

function dashboardCenterScopedHeaders(int $centerId): array
{
    $center = Center::query()->findOrFail($centerId);

    $role = Role::query()->firstOrCreate(['slug' => 'super_admin'], [
        'name' => 'super admin',
        'name_translations' => [
            'en' => 'super admin',
            'ar' => 'مدير عام',
        ],
        'description_translations' => [
            'en' => 'System super admin role',
            'ar' => 'صلاحية مدير النظام',
        ],
    ]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) Config::get('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
        'X-Api-Key' => $systemKey,
    ];
}

it('returns system dashboard stats and recent activity', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $courseA = Course::factory()->create(['center_id' => $centerA->id]);
    $courseB = Course::factory()->create(['center_id' => $centerB->id]);

    $studentA = User::factory()->create(['is_student' => true, 'center_id' => $centerA->id]);
    $studentB = User::factory()->create(['is_student' => true, 'center_id' => $centerB->id]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->startOfMonth()->addDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $courseB->id,
        'center_id' => $centerB->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->startOfMonth()->addDays(2),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Pending->value,
        'enrolled_at' => now()->subDay(),
    ]);

    DeviceChangeRequest::factory()->create([
        'user_id' => $studentA->id,
        'center_id' => $centerA->id,
        'status' => DeviceChangeRequestStatus::Pending->value,
    ]);

    $video = Video::factory()->create(['center_id' => $centerA->id]);
    ExtraViewRequest::query()->create([
        'user_id' => $studentA->id,
        'video_id' => $video->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => ExtraViewRequestStatus::Pending->value,
        'reason' => 'Need extra attempt',
    ]);

    $actor = User::factory()->create([
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);

    AuditLog::factory()->create([
        'user_id' => $actor->id,
        'center_id' => $centerA->id,
        'action' => AuditActions::ENROLLMENT_CREATED,
        'created_at' => now()->subMinutes(40),
    ]);
    AuditLog::factory()->create([
        'user_id' => $actor->id,
        'center_id' => $centerA->id,
        'action' => AuditActions::ENROLLMENT_CREATED,
        'created_at' => now()->subMinutes(30),
    ]);
    AuditLog::factory()->create([
        'user_id' => $actor->id,
        'center_id' => $centerA->id,
        'action' => AuditActions::EXTRA_VIEW_REQUEST_APPROVED,
        'created_at' => now()->subMinutes(20),
    ]);
    AuditLog::factory()->create([
        'user_id' => $actor->id,
        'center_id' => $centerA->id,
        'action' => AuditActions::DEVICE_CHANGE_REQUEST_APPROVED,
        'created_at' => now()->subMinutes(10),
    ]);

    $response = $this->getJson('/api/v1/admin/dashboard', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data.recent_activity')
        ->assertJsonPath('data.stats.total_courses', 2)
        ->assertJsonPath('data.stats.total_students', 2)
        ->assertJsonPath('data.stats.active_enrollments.count', 3)
        ->assertJsonPath('data.stats.active_enrollments.trend', 'up')
        ->assertJsonPath('data.stats.pending_approvals.total', 3)
        ->assertJsonPath('data.stats.pending_approvals.enrollment_requests', 1)
        ->assertJsonPath('data.stats.pending_approvals.device_change_requests', 1)
        ->assertJsonPath('data.stats.pending_approvals.extra_view_requests', 1)
        ->assertJsonPath('data.recent_activity.0.action', 'device_change_request.approved')
        ->assertJsonPath('data.recent_activity.0.actor.id', $actor->id);
});

it('applies center filter on system dashboard endpoint', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $courseA = Course::factory()->create(['center_id' => $centerA->id]);
    $courseB = Course::factory()->create(['center_id' => $centerB->id]);

    $studentA = User::factory()->create(['is_student' => true, 'center_id' => $centerA->id]);
    $studentB = User::factory()->create(['is_student' => true, 'center_id' => $centerB->id]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->startOfMonth()->addDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $courseB->id,
        'center_id' => $centerB->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->startOfMonth()->addDay(),
    ]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Pending->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $courseB->id,
        'center_id' => $centerB->id,
        'status' => EnrollmentStatus::Pending->value,
        'enrolled_at' => now()->subDay(),
    ]);

    DeviceChangeRequest::factory()->create([
        'user_id' => $studentA->id,
        'center_id' => $centerA->id,
        'status' => DeviceChangeRequestStatus::Pending->value,
    ]);
    DeviceChangeRequest::factory()->create([
        'user_id' => $studentB->id,
        'center_id' => $centerB->id,
        'status' => DeviceChangeRequestStatus::Pending->value,
    ]);

    $videoA = Video::factory()->create(['center_id' => $centerA->id]);
    $videoB = Video::factory()->create(['center_id' => $centerB->id]);

    ExtraViewRequest::query()->create([
        'user_id' => $studentA->id,
        'video_id' => $videoA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => ExtraViewRequestStatus::Pending->value,
        'reason' => 'Need extra attempt',
    ]);
    ExtraViewRequest::query()->create([
        'user_id' => $studentB->id,
        'video_id' => $videoB->id,
        'course_id' => $courseB->id,
        'center_id' => $centerB->id,
        'status' => ExtraViewRequestStatus::Pending->value,
        'reason' => 'Need extra attempt',
    ]);

    $actorA = User::factory()->create(['is_student' => false, 'center_id' => $centerA->id]);
    $actorB = User::factory()->create(['is_student' => false, 'center_id' => $centerB->id]);

    AuditLog::factory()->create([
        'user_id' => $actorA->id,
        'center_id' => $centerA->id,
        'action' => AuditActions::ENROLLMENT_CREATED,
        'created_at' => now()->subMinutes(15),
    ]);
    AuditLog::factory()->create([
        'user_id' => $actorB->id,
        'center_id' => $centerB->id,
        'action' => AuditActions::ENROLLMENT_CREATED,
        'created_at' => now()->subMinutes(10),
    ]);

    $response = $this->getJson('/api/v1/admin/dashboard?center_id='.$centerA->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.stats.total_courses', 1)
        ->assertJsonPath('data.stats.total_students', 1)
        ->assertJsonPath('data.stats.active_enrollments.count', 1)
        ->assertJsonPath('data.stats.pending_approvals.total', 3)
        ->assertJsonPath('data.recent_activity.0.actor.id', $actorA->id);
});

it('allows center-scoped super admin to access own center dashboard endpoint', function (): void {
    $this->asAdmin();

    $ownedCenter = Center::factory()->create();
    $otherCenter = Center::factory()->create();

    Course::factory()->create(['center_id' => $ownedCenter->id]);
    Course::factory()->create(['center_id' => $otherCenter->id]);

    User::factory()->create(['is_student' => true, 'center_id' => $ownedCenter->id]);
    User::factory()->create(['is_student' => true, 'center_id' => $otherCenter->id]);

    $headers = dashboardCenterScopedHeaders((int) $ownedCenter->id);

    $response = $this->getJson('/api/v1/admin/centers/'.$ownedCenter->id.'/dashboard', $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.stats.total_courses', 1)
        ->assertJsonPath('data.stats.total_students', 1);
});

it('forbids center-scoped super admin from system dashboard endpoint', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $headers = dashboardCenterScopedHeaders((int) $center->id);

    $this->getJson('/api/v1/admin/dashboard', $headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('blocks center-scoped super admin from other center dashboard endpoint', function (): void {
    $this->asAdmin();

    $ownedCenter = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $headers = dashboardCenterScopedHeaders((int) $ownedCenter->id);

    $this->getJson('/api/v1/admin/centers/'.$otherCenter->id.'/dashboard', $headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});
