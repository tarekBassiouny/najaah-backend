<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\UserDeviceStatus;
use App\Enums\VideoAccessCodeStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoAccessCode;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('video-access', 'mobile');

beforeEach(function (): void {
    config([
        'services.system_api_key' => 'system-key',
        'bunny.api.api_key' => 'bunny-secret',
        'bunny.api.library_id' => 55,
        'bunny.embed_key' => 'test-embed-secret-key',
    ]);

    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'video-redemption-device');
});

/**
 * @return array{0:Center,1:Course,2:Video,3:User,4:Enrollment}
 */
function buildRedemptionContext($testCase): array
{
    $center = Center::factory()->create([
        'default_view_limit' => 2,
    ]);

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
        'requires_video_approval' => null,
    ]);

    $upload = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'library_id' => 55,
        'source_id' => 'video-uuid-'.$center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $upload->id,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);
    $student->center_id = $center->id;
    $student->save();
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);
    $testCase->asApiUser($student, null, 'video-redemption-device');

    UserDevice::factory()->create([
        'user_id' => $student->id,
        'status' => UserDeviceStatus::Active,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    return [$center, $course, $video, $student, $enrollment];
}

test('playback is blocked until code redemption when approval is required', function (): void {
    [$center, $course, $video] = buildRedemptionContext($this);

    $response = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback"
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'VIDEO_ACCESS_DENIED');
});

test('student redeems code and playback becomes available', function (): void {
    [$center, $course, $video, $student, $enrollment] = buildRedemptionContext($this);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'code' => 'A1B2C3D4',
        'status' => VideoAccessCodeStatus::Active,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $redeem = $this->apiPost('/api/v1/video-access-codes/redeem', [
        'code' => $code->code,
    ]);

    $redeem->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.video_id', $video->id);

    $this->assertDatabaseHas('video_access_codes', [
        'id' => $code->id,
        'status' => VideoAccessCodeStatus::Used->value,
    ]);

    $this->assertDatabaseHas('video_accesses', [
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'video_access_code_id' => $code->id,
    ]);

    $playback = $this->apiPost(
        "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback"
    );

    $playback->assertOk()
        ->assertJsonPath('success', true);
});

test('student cannot redeem code belonging to another student', function (): void {
    [$center, $course, $video, $student, $enrollment] = buildRedemptionContext($this);

    $code = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'code' => 'Z9Y8X7W6',
        'status' => VideoAccessCodeStatus::Active,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $otherStudent->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Enrollment::factory()->create([
        'user_id' => $otherStudent->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    UserDevice::factory()->create([
        'user_id' => $otherStudent->id,
        'status' => UserDeviceStatus::Active,
    ]);

    $this->asApiUser($otherStudent, null, 'video-redemption-device-2');

    $response = $this->apiPost('/api/v1/video-access-codes/redeem', [
        'code' => $code->code,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'VIDEO_CODE_WRONG_USER');
});

test('redeeming a new code after revoke reuses the same video access row', function (): void {
    [$center, $course, $video, $student, $enrollment] = buildRedemptionContext($this);

    $firstCode = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'code' => 'QW12ER34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->apiPost('/api/v1/video-access-codes/redeem', [
        'code' => $firstCode->code,
    ])->assertOk();

    $firstAccessId = \App\Models\VideoAccess::query()
        ->where('user_id', $student->id)
        ->where('video_id', $video->id)
        ->where('course_id', $course->id)
        ->value('id');

    expect($firstAccessId)->toBeInt();

    \App\Models\VideoAccess::query()
        ->whereKey($firstAccessId)
        ->update([
            'revoked_at' => now(),
        ]);

    $secondCode = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'code' => 'TY56UI78',
        'status' => VideoAccessCodeStatus::Active,
        'generated_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->apiPost('/api/v1/video-access-codes/redeem', [
        'code' => $secondCode->code,
    ])->assertOk();

    $this->assertDatabaseCount('video_accesses', 1);
    $this->assertDatabaseHas('video_accesses', [
        'id' => $firstAccessId,
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'video_access_code_id' => $secondCode->id,
        'revoked_at' => null,
    ]);
});
