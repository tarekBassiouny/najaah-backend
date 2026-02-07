<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\PlaybackSession;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class)->group('analytics', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    $this->withoutMiddleware(Authenticate::class);
});

function createAnalyticsCenterAdmin(Center $center): User
{
    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $role = Role::firstOrCreate(['slug' => 'center_admin'], [
        'name' => 'center admin',
        'name_translations' => ['en' => 'center admin', 'ar' => 'مدير المركز'],
        'description_translations' => ['en' => 'Center administrator', 'ar' => 'مدير المركز'],
    ]);

    $permission = Permission::firstOrCreate(['name' => 'audit.view'], [
        'description' => 'Permission: audit.view',
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

function getAnalyticsAdminToken(User $admin): string
{
    return (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);
}

function analyticsAdminHeadersFor(string $token): array
{
    $systemKey = (string) Config::get('services.system_api_key', 'system-test-key');
    Config::set('services.system_api_key', $systemKey);

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
        'X-Api-Key' => $systemKey,
    ];
}

it('scopes overview analytics to all centers for system admins', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create(['type' => CenterType::Unbranded->value]);
    $centerB = Center::factory()->create(['type' => CenterType::Branded->value]);

    $courseA = Course::factory()->for($centerA, 'center')->create([
        'center_id' => $centerA->id,
        'category_id' => Category::factory()->for($centerA, 'center'),
        'created_by' => User::factory()->for($centerA, 'center'),
        'status' => CourseStatus::Published->value,
        'is_published' => true,
    ]);
    $courseB = Course::factory()->for($centerB, 'center')->create([
        'center_id' => $centerB->id,
        'category_id' => Category::factory()->for($centerB, 'center'),
        'created_by' => User::factory()->for($centerB, 'center'),
        'status' => CourseStatus::Published->value,
        'is_published' => true,
    ]);

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $courseB->id,
        'center_id' => $centerB->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/v1/admin/analytics/overview?from={$from}&to={$to}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.overview.total_centers', 2)
        ->assertJsonPath('data.overview.total_courses', 2)
        ->assertJsonPath('data.overview.total_enrollments', 2);
});

it('scopes overview analytics to the admin center for center admins', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    Course::factory()->for($centerA, 'center')->create([
        'center_id' => $centerA->id,
        'category_id' => Category::factory()->for($centerA, 'center'),
        'created_by' => User::factory()->for($centerA, 'center'),
    ]);
    Course::factory()->for($centerB, 'center')->create([
        'center_id' => $centerB->id,
        'category_id' => Category::factory()->for($centerB, 'center'),
        'created_by' => User::factory()->for($centerB, 'center'),
    ]);

    $admin = createAnalyticsCenterAdmin($centerA);
    $token = getAnalyticsAdminToken($admin);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/v1/admin/analytics/overview?from={$from}&to={$to}", analyticsAdminHeadersFor($token));

    $response->assertOk()
        ->assertJsonPath('data.overview.total_centers', 1)
        ->assertJsonPath('data.overview.total_courses', 1);
});

it('rejects center admins requesting analytics for other centers', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = createAnalyticsCenterAdmin($centerA);
    $token = getAnalyticsAdminToken($admin);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson(
        "/api/v1/admin/analytics/overview?center_id={$centerB->id}&from={$from}&to={$to}",
        analyticsAdminHeadersFor($token)
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('returns approved and rejected enrollment counts in devices analytics', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $course = Course::factory()->for($center, 'center')->create([
        'center_id' => $center->id,
        'category_id' => Category::factory()->for($center, 'center'),
        'created_by' => User::factory()->for($center, 'center'),
    ]);

    $pendingStudent = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $approvedStudent = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $cancelledStudent = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $deactivatedStudent = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);

    Enrollment::factory()->create([
        'user_id' => $pendingStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Pending->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $approvedStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $cancelledStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Cancelled->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $deactivatedStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Deactivated->value,
        'enrolled_at' => now()->subDay(),
    ]);
    Enrollment::factory()->create([
        'user_id' => $approvedStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDays(40),
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/v1/admin/analytics/devices-requests?from={$from}&to={$to}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.requests.enrollment.pending', 1)
        ->assertJsonPath('data.requests.enrollment.approved', 1)
        ->assertJsonPath('data.requests.enrollment.rejected', 2);
});

it('returns cumulative views and session analytics for a student', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $course = Course::factory()->for($center, 'center')->create([
        'center_id' => $center->id,
        'category_id' => Category::factory()->for($center, 'center'),
        'created_by' => User::factory()->for($center, 'center'),
        'status' => CourseStatus::Published->value,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'duration_seconds' => 1000,
    ]);
    $course->videos()->attach($video->id);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHours(3),
        'ended_at' => now()->subHours(2),
        'watch_duration' => 300,
        'progress_percent' => 30,
        'is_full_play' => false,
    ]);
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'watch_duration' => 300,
        'progress_percent' => 40,
        'is_full_play' => false,
    ]);
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHour(),
        'ended_at' => now(),
        'watch_duration' => 300,
        'progress_percent' => 50,
        'is_full_play' => false,
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson(
        "/api/v1/admin/analytics/students?student_id={$student->id}&from={$from}&to={$to}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.overview.total_views', 1)
        ->assertJsonPath('data.overview.total_sessions', 3)
        ->assertJsonPath('data.overview.active_enrollments', 1)
        ->assertJsonPath('data.courses.views.0.views', 1)
        ->assertJsonPath('data.videos.0.is_viewed', true);
});

it('returns analytics responses with expected shapes', function (): void {
    $this->asAdmin();

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $overview = $this->getJson("/api/v1/admin/analytics/overview?from={$from}&to={$to}", $this->adminHeaders());
    $overview->assertOk()->assertJsonStructure([
        'success',
        'data' => [
            'meta' => [
                'range' => ['from', 'to'],
                'center_id',
                'timezone',
                'generated_at',
            ],
            'overview' => [
                'total_centers',
                'active_centers',
                'centers_by_type' => ['unbranded', 'branded'],
                'total_courses',
                'published_courses',
                'total_enrollments',
                'active_enrollments',
                'daily_active_learners',
            ],
        ],
    ]);

    $coursesMedia = $this->getJson("/api/v1/admin/analytics/courses-media?from={$from}&to={$to}", $this->adminHeaders());
    $coursesMedia->assertOk()->assertJsonStructure([
        'success',
        'data' => [
            'meta' => [
                'range' => ['from', 'to'],
                'center_id',
                'timezone',
                'generated_at',
            ],
            'courses' => [
                'by_status' => ['draft', 'uploading', 'ready', 'published', 'archived'],
                'ready_to_publish',
                'blocked_by_media',
                'top_by_enrollments',
            ],
            'media' => [
                'videos' => [
                    'total',
                    'by_upload_status' => ['pending', 'uploading', 'processing', 'ready', 'failed'],
                    'by_lifecycle_status' => ['pending', 'processing', 'ready'],
                ],
                'pdfs' => [
                    'total',
                    'by_upload_status' => ['pending', 'processing', 'ready'],
                ],
            ],
        ],
    ]);

    $learners = $this->getJson("/api/v1/admin/analytics/learners-enrollments?from={$from}&to={$to}", $this->adminHeaders());
    $learners->assertOk()->assertJsonStructure([
        'success',
        'data' => [
            'meta' => [
                'range' => ['from', 'to'],
                'center_id',
                'timezone',
                'generated_at',
            ],
            'learners' => [
                'total_students',
                'active_students',
                'new_students',
                'by_center',
            ],
            'enrollments' => [
                'by_status' => ['active', 'pending', 'deactivated', 'cancelled'],
                'top_courses',
            ],
        ],
    ]);

    $devices = $this->getJson("/api/v1/admin/analytics/devices-requests?from={$from}&to={$to}", $this->adminHeaders());
    $devices->assertOk()->assertJsonStructure([
        'success',
        'data' => [
            'meta' => [
                'range' => ['from', 'to'],
                'center_id',
                'timezone',
                'generated_at',
            ],
            'devices' => [
                'total',
                'active',
                'revoked',
                'pending',
                'changes' => [
                    'pending',
                    'approved',
                    'rejected',
                    'pre_approved',
                    'by_source' => ['mobile', 'otp', 'admin'],
                ],
            ],
            'requests' => [
                'extra_views' => [
                    'pending',
                    'approved',
                    'rejected',
                    'approval_rate',
                    'avg_decision_hours',
                ],
                'enrollment' => [
                    'pending',
                    'approved',
                    'rejected',
                ],
            ],
        ],
    ]);
});
