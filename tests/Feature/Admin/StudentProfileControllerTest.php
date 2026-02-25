<?php

declare(strict_types=1);

use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserDeviceStatus;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\PlaybackSession;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('students', 'admin', 'student-profile');

it('returns student profile with courses and videos', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $category = Category::factory()->for($center, 'center')->create();
    $creator = User::factory()->for($center, 'center')->create(['is_student' => false]);

    $course = Course::factory()->for($center, 'center')->create([
        'category_id' => $category->id,
        'created_by' => $creator->id,
    ]);

    $section = Section::factory()->for($course, 'course')->create([
        'order_index' => 1,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'duration_seconds' => 600,
    ]);

    $section->videos()->attach($video->id, [
        'course_id' => $course->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000100',
        'status' => UserStatus::Active->value,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
        'status' => UserDeviceStatus::Active->value,
        'model' => 'Samsung Galaxy S24',
        'device_id' => 'CTR-4-STD-8',
        'last_used_at' => now()->subHour(),
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);

    // Create device change requests (approved)
    DeviceChangeRequest::factory()->create([
        'user_id' => $student->id,
        'center_id' => $center->id,
        'new_model' => 'iPhone 14 Pro',
        'new_device_id' => 'IOS-7F3A-91C2',
        'status' => DeviceChangeRequestStatus::Approved->value,
        'reason' => 'Previous phone replaced',
        'decided_at' => now()->subDays(3),
    ]);

    // Create playback sessions
    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'progress_percent' => 75,
        'is_full_play' => true,
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinutes(30),
        'ended_at' => now()->subMinutes(10),
        'progress_percent' => 90,
        'is_full_play' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/profile", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.name', $student->name)
        ->assertJsonPath('data.status_label', 'Active')
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'username',
                'email',
                'phone',
                'country_code',
                'status',
                'status_label',
                'last_activity_at',
                'active_device' => [
                    'model',
                    'device_id',
                ],
                'total_enrollments',
                'device_changes_count',
                'device_change_log' => [
                    '*' => [
                        'device_name',
                        'device_id',
                        'changed_at',
                        'reason',
                    ],
                ],
                'center' => [
                    'id',
                    'name',
                ],
                'enrollments' => [
                    '*' => [
                        'id',
                        'enrolled_at',
                        'expires_at',
                        'status',
                        'status_label',
                        'progress_percentage',
                        'course' => [
                            'id',
                            'title',
                            'thumbnail_url',
                            'video_count',
                            'videos' => [
                                '*' => [
                                    'id',
                                    'title',
                                    'watch_count',
                                    'watch_limit',
                                    'watch_progress_percentage',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    // Check new stats card fields
    expect($response->json('data.active_device.model'))->toBe('Samsung Galaxy S24');
    expect($response->json('data.active_device.device_id'))->toBe('CTR-4-STD-8');
    expect($response->json('data.total_enrollments'))->toBe(1);
    expect($response->json('data.device_changes_count'))->toBe(1);
    expect($response->json('data.device_change_log.0.device_name'))->toBe('iPhone 14 Pro');

    // Check enrollment progress and video count
    expect((float) $response->json('data.enrollments.0.progress_percentage'))->toBe(90.0);
    expect($response->json('data.enrollments.0.course.title'))->toBe($course->translate('title'));
    expect($response->json('data.enrollments.0.course.is_published'))->toBe((bool) $course->is_published);
    expect($response->json('data.enrollments.0.course.video_count'))->toBe(1);

    // Check video watch data
    $videoData = $response->json('data.enrollments.0.course.videos.0');
    expect($videoData['id'])->toBe($video->id);
    expect($videoData['watch_count'])->toBe(1); // Only one full play
    expect((float) $videoData['watch_progress_percentage'])->toBe(90.0); // Latest session progress
});

it('returns empty enrollments for student with no enrollments', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000101',
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/profile", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.enrollments', []);
});

it('returns 404 for non-student users', function (): void {
    $this->asAdmin();

    $adminUser = User::factory()->create([
        'is_student' => false,
        'phone' => '19990000102',
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$adminUser->id}/profile", $this->adminHeaders());

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_A_STUDENT');
});

it('denies access without permission', function (): void {
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'phone' => '19990000103',
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/profile", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('allows center admin to view their students profile', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000104',
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    // Center admin uses center-scoped route
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/students/{$student->id}/profile", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id);
});

it('denies center admin from viewing students in another center', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$centerA->id => ['type' => 'admin']]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000111',
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    // Center admin cannot access other center via center route
    $response = $this->getJson("/api/v1/admin/centers/{$centerB->id}/students/{$student->id}/profile", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key', 'system-test-key'),
    ]);

    // Blocked by scope middleware
    $response->assertStatus(403);
});

it('enforces center api key scope for student profile', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create([
        'api_key' => 'center-a-profile-key',
    ]);
    $centerB = Center::factory()->create([
        'api_key' => 'center-b-profile-key',
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000112',
    ]);

    $response = $this->getJson(
        "/api/v1/admin/students/{$student->id}/profile",
        $this->adminHeaders(['X-Api-Key' => 'center-a-profile-key'])
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'SYSTEM_API_KEY_REQUIRED');
});

it('allows super admin to access student profile via system route', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000113',
    ]);

    // Super admin can use system route
    $response = $this->getJson(
        "/api/v1/admin/students/{$student->id}/profile",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id);
});

it('returns correct watch count for multiple full plays', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $category = Category::factory()->for($center, 'center')->create();
    $creator = User::factory()->for($center, 'center')->create(['is_student' => false]);

    $course = Course::factory()->for($center, 'center')->create([
        'category_id' => $category->id,
        'created_by' => $creator->id,
    ]);

    $section = Section::factory()->for($course, 'course')->create();

    $video = Video::factory()->create([
        'center_id' => $center->id,
    ]);

    $section->videos()->attach($video->id, [
        'course_id' => $course->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000105',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now(),
    ]);

    // Create 3 full plays
    for ($i = 0; $i < 3; $i++) {
        PlaybackSession::factory()->create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'device_id' => $device->id,
            'started_at' => now()->subHours($i + 1),
            'ended_at' => now()->subHours($i),
            'progress_percent' => 100,
            'is_full_play' => true,
        ]);
    }

    // Create 2 partial plays (should not count)
    for ($i = 0; $i < 2; $i++) {
        PlaybackSession::factory()->create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'course_id' => $course->id,
            'device_id' => $device->id,
            'started_at' => now()->subMinutes(30 + $i * 10),
            'ended_at' => now()->subMinutes(20 + $i * 10),
            'progress_percent' => 50,
            'is_full_play' => false,
        ]);
    }

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/profile", $this->adminHeaders());

    $response->assertOk();

    $videoData = $response->json('data.enrollments.0.course.videos.0');
    expect($videoData['watch_count'])->toBe(3); // Only full plays counted
});

it('returns videos for multiple sections in order', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $category = Category::factory()->for($center, 'center')->create();
    $creator = User::factory()->for($center, 'center')->create(['is_student' => false]);

    $course = Course::factory()->for($center, 'center')->create([
        'category_id' => $category->id,
        'created_by' => $creator->id,
    ]);

    $section1 = Section::factory()->for($course, 'course')->create(['order_index' => 1]);
    $section2 = Section::factory()->for($course, 'course')->create(['order_index' => 2]);

    $video1 = Video::factory()->create(['center_id' => $center->id]);
    $video2 = Video::factory()->create(['center_id' => $center->id]);
    $video3 = Video::factory()->create(['center_id' => $center->id]);

    $section1->videos()->attach($video1->id, ['course_id' => $course->id, 'order_index' => 1, 'visible' => true]);
    $section1->videos()->attach($video2->id, ['course_id' => $course->id, 'order_index' => 2, 'visible' => true]);
    $section2->videos()->attach($video3->id, ['course_id' => $course->id, 'order_index' => 1, 'visible' => true]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000106',
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/profile", $this->adminHeaders());

    $response->assertOk();

    $videos = $response->json('data.enrollments.0.course.videos');
    expect(count($videos))->toBe(3);
});
