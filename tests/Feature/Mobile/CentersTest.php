<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'centers');

function attachReadyCenterCourseVideo(Course $course, Center $center): Video
{
    $readySession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $readySession->id,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    return $video;
}

it('blocks branded students from listing centers', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('lists unbranded centers for system students', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    Center::factory()->create(['type' => 1]);

    CenterSetting::factory()->create([
        'center_id' => $unbranded->id,
        'settings' => ['theme' => ['primary' => '#123456']],
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $unbranded->id)
        ->assertJsonPath('data.0.theme.primary', '#123456');
});

it('does not list inactive unbranded centers', function (): void {
    Center::factory()->create([
        'type' => 0,
        'status' => Center::STATUS_INACTIVE,
    ]);
    $activeCenter = Center::factory()->create([
        'type' => 0,
        'status' => Center::STATUS_ACTIVE,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeCenter->id);
});

it('searches centers by name and description', function (): void {
    $matchName = Center::factory()->create([
        'type' => 0,
        'name_translations' => ['en' => 'Alpha Center'],
    ]);
    $matchDescription = Center::factory()->create([
        'type' => 0,
        'description_translations' => ['en' => 'Science hub'],
    ]);
    Center::factory()->create([
        'type' => 0,
        'name_translations' => ['en' => 'Other Center'],
        'description_translations' => ['en' => 'Other'],
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $byName = $this->apiGet('/api/v1/centers?search=Alpha');
    $byName->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchName->id);

    $byDescription = $this->apiGet('/api/v1/centers?search=Science');
    $byDescription->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchDescription->id);
});

it('shows center with courses for unbranded students', function (): void {
    $center = Center::factory()->create(['type' => 0]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCenterCourseVideo($course, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$center->id);

    $response->assertOk()
        ->assertJsonPath('data.center.id', $center->id)
        ->assertJsonPath('data.courses.0.id', $course->id)
        ->assertJsonPath('data.courses.0.is_enrolled', true);
});

it('rejects branded centers for unbranded students', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$center->id);

    $response->assertStatus(404);
});

it('rejects inactive centers for unbranded students', function (): void {
    $center = Center::factory()->create([
        'type' => 0,
        'status' => Center::STATUS_INACTIVE,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$center->id);

    $response->assertStatus(404);
});

it('paginates center list', function (): void {
    Center::factory()->count(2)->create(['type' => 0]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('paginates center courses list', function (): void {
    $center = Center::factory()->create(['type' => 0]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $courseA = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $courseB = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCenterCourseVideo($courseA, $center);
    attachReadyCenterCourseVideo($courseB, $center);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$center->id.'?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data.courses')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('returns validation errors for invalid pagination', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers?per_page=0&page=0');

    $response->assertStatus(422);
});
