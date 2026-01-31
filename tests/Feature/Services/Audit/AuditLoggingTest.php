<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\AdminUsers\AdminUserService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Centers\CenterService;
use App\Services\Courses\CourseService;
use App\Services\Playback\ViewLimitService;
use App\Services\Requests\RequestService;
use App\Services\Roles\RoleService;
use App\Services\Settings\SettingsResolverService;
use App\Support\AuditActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('audit', 'services');

test('role creation is audited with actor', function (): void {
    $admin = $this->asAdmin();
    $service = new RoleService(new AuditLogService);

    $role = $service->create([
        'name_translations' => ['en' => 'Reviewer'],
        'description_translations' => ['en' => 'Review role'],
        'slug' => 'reviewer',
    ], $admin);

    $log = AuditLog::where('action', AuditActions::ROLE_CREATED)
        ->where('entity_type', Role::class)
        ->where('entity_id', $role->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
});

test('admin user creation is audited with actor', function (): void {
    $admin = $this->asAdmin();
    $service = new AdminUserService(new AuditLogService);

    $created = $service->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '01000000000',
        'password' => 'secret123',
        'status' => 1,
    ], $admin);

    $log = AuditLog::where('action', AuditActions::ADMIN_USER_CREATED)
        ->where('entity_type', User::class)
        ->where('entity_id', $created->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
});

test('center update is audited with actor', function (): void {
    $admin = $this->asAdmin();
    $service = new CenterService(new AuditLogService);
    $center = Center::factory()->create();

    $service->update($center, ['name' => 'Updated Center'], $admin);

    $log = AuditLog::where('action', AuditActions::CENTER_UPDATED)
        ->where('entity_type', Center::class)
        ->where('entity_id', $center->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
});

test('course creation is audited with actor', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $service = new CourseService(new CenterScopeService, new AuditLogService);

    $course = $service->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Test Course'],
        'description_translations' => ['en' => 'Test'],
        'difficulty_level' => 1,
        'language' => 'en',
        'created_by' => $admin->id,
    ], $admin);

    $log = AuditLog::where('action', AuditActions::COURSE_CREATED)
        ->where('entity_type', $course::class)
        ->where('entity_id', $course->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
});

test('extra view request creation is audited with actor', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 0]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $video = Video::factory()->create(['center_id' => $center->id]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $service = new RequestService(
        new ViewLimitService(new SettingsResolverService),
        new StudentAccessService,
        new CourseAccessService,
        new EnrollmentAccessService,
        new AuditLogService
    );

    $service->createExtraViewRequest($student, $center, $course, $video, 'Need more views');

    $request = ExtraViewRequest::query()
        ->where('user_id', $student->id)
        ->where('video_id', $video->id)
        ->first();

    $log = AuditLog::where('action', AuditActions::EXTRA_VIEW_REQUEST_CREATED)
        ->where('entity_type', ExtraViewRequest::class)
        ->where('entity_id', $request?->id)
        ->first();

    expect($request)->not->toBeNull();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($student->id);
});

test('enrollment request creation is audited with actor', function (): void {
    $center = Center::factory()->create();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $service = new RequestService(
        new ViewLimitService(new SettingsResolverService),
        new StudentAccessService,
        new CourseAccessService,
        new EnrollmentAccessService,
        new AuditLogService
    );

    $service->createEnrollmentRequest($student, $center, $course, 'Need access');

    $enrollment = Enrollment::query()
        ->where('user_id', $student->id)
        ->where('course_id', $course->id)
        ->where('status', Enrollment::STATUS_PENDING)
        ->first();

    $log = AuditLog::where('action', AuditActions::ENROLLMENT_REQUEST_CREATED)
        ->where('entity_type', Enrollment::class)
        ->where('entity_id', $enrollment?->id)
        ->first();

    expect($enrollment)->not->toBeNull();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($student->id);
});

test('device change request creation is audited with actor', function (): void {
    $student = User::factory()->create(['is_student' => true]);

    UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'old-device',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $service = new RequestService(
        new ViewLimitService(new SettingsResolverService),
        new StudentAccessService,
        new CourseAccessService,
        new EnrollmentAccessService,
        new AuditLogService
    );

    $service->createDeviceChangeRequest($student, 'Lost device');

    $request = DeviceChangeRequest::query()
        ->where('user_id', $student->id)
        ->first();

    $log = AuditLog::where('action', AuditActions::DEVICE_CHANGE_REQUEST_CREATED)
        ->where('entity_type', DeviceChangeRequest::class)
        ->where('entity_id', $request?->id)
        ->first();

    expect($request)->not->toBeNull();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($student->id);
});
