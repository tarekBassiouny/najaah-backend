<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\JwtToken;
use App\Models\PlaybackSession;
use App\Models\Section;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class)->group('me', 'mobile');

beforeEach(function (): void {
    config(['services.system_api_key' => 'system-key']);
});

function authHeaders(?string $token = null, ?string $apiKey = null): array
{
    $headers = [
        'X-Api-Key' => $apiKey ?? 'system-key',
    ];

    if (is_string($token) && $token !== '') {
        $headers['Authorization'] = 'Bearer '.$token;
    }

    return $headers;
}

test('returns current student on /auth/me', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.center.id', $user->center_id)
        ->assertJsonPath('data.is_complete_profile', true)
        ->assertJsonPath('data.profile_completion.missing_steps', [])
        ->assertJsonPath('data.device.device_id', $device->device_id)
        ->assertJsonPath('data.device.device_name', $device->device_name)
        ->assertJsonPath('data.device.device_type', $device->device_type);
});

test('returns detailed current student profile on /auth/me/profile', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'center-me-profile-key',
    ]);
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
    ]);

    $section->videos()->attach($video->id, [
        'course_id' => $course->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->subMinutes(20),
        'progress_percent' => 85,
        'is_full_play' => true,
    ]);

    $response = $this->getJson('/api/v1/auth/me/profile', authHeaders($access, 'center-me-profile-key'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.center.id', $center->id)
        ->assertJsonPath('data.is_complete_profile', true)
        ->assertJsonPath('data.profile_completion.missing_steps', [])
        ->assertJsonPath('data.enrollments.0.course.id', $course->id)
        ->assertJsonPath('data.enrollments.0.course.videos.0.id', $video->id)
        ->assertJsonPath('data.enrollments.0.course.videos.0.watch_count', 1);
});

test('rejects /auth/me/profile when center api key scope mismatches student center', function (): void {
    $centerA = Center::factory()->create([
        'api_key' => 'center-a-mobile-key',
    ]);
    Center::factory()->create([
        'api_key' => 'center-b-mobile-key',
    ]);

    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $centerA->id,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me/profile', [
        'Authorization' => 'Bearer '.$access,
        'X-Api-Key' => 'center-b-mobile-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('allows system-scope students without center assignment on /auth/me/profile', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me/profile', authHeaders($access));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.center', null);
});

test('rejects unauthorized /auth/me', function (): void {
    $response = $this->getJson('/api/v1/auth/me', authHeaders());

    $response->assertStatus(403);
});

test('revokes token on logout and blocks reuse', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $logout = $this->postJson('/api/v1/auth/logout', [], authHeaders($access));

    $logout->assertOk()->assertJsonPath('success', true);

    $reuse = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $reuse->assertStatus(403);
});

test('blocks revoked access tokens', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
        'revoked_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('blocks expired access tokens', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->subMinute(),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('blocks tokens for revoked devices', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'status' => UserDevice::STATUS_REVOKED,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('blocks inactive students even with a valid token', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'status' => UserStatus::Inactive->value,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'STUDENT_INACTIVE');
});

test('allows system-level students without center assignment', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $token = JWTAuth::fromUser($student);
    JwtToken::create([
        'user_id' => $student->id,
        'device_id' => null,
        'access_token' => $token,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($token));

    $response->assertOk()
        ->assertJsonPath('data.center_id', null)
        ->assertJsonPath('data.center.id', null);
});

test('updates student profile name', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'name' => 'Old Name',
        'center_id' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->postJson('/api/v1/auth/me', [
        'name' => 'New Name',
    ], authHeaders($access));

    $response->assertOk()->assertJsonPath('data.name', 'New Name');
    $response->assertJsonPath('data.is_complete_profile', true);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});

test('updates student parent phone on profile endpoint', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'center-parent-phone-update-key',
    ]);

    \App\Models\CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
                'enable_parent_phone' => true,
                'require_grade' => false,
                'require_school' => false,
                'require_college' => false,
                'require_parent_phone' => true,
            ],
        ],
    ]);

    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'name' => 'Student Name',
        'center_id' => $center->id,
        'parent_phone' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->postJson('/api/v1/auth/me', [
        'parent_phone' => '+20 100 123 4567',
    ], authHeaders($access, 'center-parent-phone-update-key'));

    $response->assertOk()
        ->assertJsonPath('data.parent_phone', '+201001234567')
        ->assertJsonPath('data.is_complete_profile', true)
        ->assertJsonPath('data.profile_completion.missing_steps', []);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'parent_phone' => '+201001234567',
    ]);
});

test('rejects blank name updates on profile endpoint', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'center-blank-name-update-key',
    ]);

    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'name' => 'Student Name',
        'center_id' => $center->id,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->postJson('/api/v1/auth/me', [
        'name' => '   ',
    ], authHeaders($access, 'center-blank-name-update-key'));

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('returns incomplete completion state on /auth/me/profile when placeholder name and required education are missing', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'center-me-profile-completion-key',
    ]);

    \App\Models\CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
                'enable_parent_phone' => true,
                'require_grade' => true,
                'require_school' => false,
                'require_college' => false,
                'require_parent_phone' => true,
            ],
        ],
    ]);

    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'name' => 'Student',
        'center_id' => $center->id,
        'grade_id' => null,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me/profile', authHeaders($access, 'center-me-profile-completion-key'));

    $response->assertOk()
        ->assertJsonPath('data.is_complete_profile', false)
        ->assertJsonPath('data.profile_completion.missing_steps', ['name', 'parent', 'education'])
        ->assertJsonPath('data.profile_completion.missing_fields', ['name', 'parent_phone', 'grade_id']);
});
