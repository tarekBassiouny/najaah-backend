<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Enums\VideoAccessCodeStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('video-access', 'admin', 'code-generation');

/**
 * @return array{0:Center,1:Course,2:Video,3:User,4:Enrollment}
 */
function buildVideoAccessCodeGenerationContext(): array
{
    $center = Center::factory()->create();
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

    return [$center, $course, $video, $student, $enrollment];
}

it('blocks generating a new code when a valid active code already exists', function (): void {
    [$center, $course, $video, $student, $enrollment] = buildVideoAccessCodeGenerationContext();
    $admin = $this->asCenterAdmin($center);

    VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'ZX12CV34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/students/{$student->id}/video-access-codes",
        [
            'video_id' => $video->id,
            'course_id' => $course->id,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VIDEO_CODE_ACTIVE_EXISTS')
        ->assertJsonPath('error.message', fn ($value) => is_string($value) && str_contains($value, 'already has a valid video access code'));

    expect(VideoAccessCode::query()->count())->toBe(1);
});

it('allows generating a new code when previous active code is expired', function (): void {
    [$center, $course, $video, $student, $enrollment] = buildVideoAccessCodeGenerationContext();
    $admin = $this->asCenterAdmin($center);

    $expiredCode = VideoAccessCode::query()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
        'video_access_request_id' => null,
        'code' => 'ER12TY34',
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now()->subDays(2),
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/students/{$student->id}/video-access-codes",
        [
            'video_id' => $video->id,
            'course_id' => $course->id,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', VideoAccessCodeStatus::Active->value);

    $expiredCode->refresh();
    expect($expiredCode->status)->toBe(VideoAccessCodeStatus::Expired);
    expect(VideoAccessCode::query()->count())->toBe(2);
});

it('allows system-scope single code generation for a student', function (): void {
    [$center, $course, $video, $student] = buildVideoAccessCodeGenerationContext();
    $this->asAdmin();

    $response = $this->postJson(
        "/api/v1/admin/students/{$student->id}/video-access-codes",
        [
            'video_id' => $video->id,
            'course_id' => $course->id,
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.student.id', $student->id)
        ->assertJsonPath('data.course.id', $course->id)
        ->assertJsonPath('data.video.id', $video->id);

    $this->assertDatabaseHas('video_access_codes', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'video_id' => $video->id,
        'center_id' => $center->id,
    ]);
});

it('allows system-scope bulk code generation for multiple students', function (): void {
    [$center, $course, $video] = buildVideoAccessCodeGenerationContext();
    $this->asAdmin();

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $studentA->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);
    Enrollment::factory()->create([
        'user_id' => $studentB->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $response = $this->postJson(
        '/api/v1/admin/video-access-codes/bulk',
        [
            'student_ids' => [$studentA->id, $studentB->id],
            'video_id' => $video->id,
            'course_id' => $course->id,
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.generated', 2)
        ->assertJsonPath('data.counts.failed', 0);

    $this->assertDatabaseHas('video_access_codes', [
        'user_id' => $studentA->id,
        'course_id' => $course->id,
        'video_id' => $video->id,
    ]);
    $this->assertDatabaseHas('video_access_codes', [
        'user_id' => $studentB->id,
        'course_id' => $course->id,
        'video_id' => $video->id,
    ]);
});
