<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'search');

function attachReadyVideoToCourse(Course $course, Center $center): Video
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

it('searches courses by title', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Alpha Biology'],
        'status' => 3,
        'is_published' => true,
    ]);
    Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Other Course'],
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyVideoToCourse($match, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $match->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search?search=Alpha');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.is_enrolled', true);
});

it('searches courses by instructor name', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Professor Alpha'],
    ]);

    $match = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $match->instructors()->syncWithoutDetaching([$instructor->id]);

    attachReadyVideoToCourse($match, $center);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search?search=Alpha');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('returns fallback courses for empty query', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $video = attachReadyVideoToCourse($course, $center);

    $device = UserDevice::factory()->create(['user_id' => $student->id]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinutes(10),
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $course->id);
});

it('scopes search results to student center or unbranded centers', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $systemStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $unbrandedCourse = Course::factory()->create([
        'center_id' => $unbranded->id,
        'title_translations' => ['en' => 'System Course'],
        'status' => 3,
        'is_published' => true,
    ]);
    $brandedCourse = Course::factory()->create([
        'center_id' => $branded->id,
        'title_translations' => ['en' => 'Branded Course'],
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyVideoToCourse($unbrandedCourse, $unbranded);
    attachReadyVideoToCourse($brandedCourse, $branded);

    $this->asApiUser($systemStudent);

    $response = $this->apiGet('/api/v1/search?search=Course');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $unbrandedCourse->id);
});

it('excludes inactive unbranded center courses from search for system students', function (): void {
    $activeUnbranded = Center::factory()->create([
        'type' => 0,
        'status' => Center::STATUS_ACTIVE,
    ]);
    $inactiveUnbranded = Center::factory()->create([
        'type' => 0,
        'status' => Center::STATUS_INACTIVE,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $activeCourse = Course::factory()->create([
        'center_id' => $activeUnbranded->id,
        'title_translations' => ['en' => 'Shared Course'],
        'status' => 3,
        'is_published' => true,
    ]);
    $inactiveCourse = Course::factory()->create([
        'center_id' => $inactiveUnbranded->id,
        'title_translations' => ['en' => 'Shared Course'],
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyVideoToCourse($activeCourse, $activeUnbranded);
    attachReadyVideoToCourse($inactiveCourse, $inactiveUnbranded);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search?search=Shared');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeCourse->id);
});

it('paginates search results', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $first = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Course One'],
        'status' => 3,
        'is_published' => true,
    ]);
    $second = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Course Two'],
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyVideoToCourse($first, $center);
    attachReadyVideoToCourse($second, $center);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search?search=Course&per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('returns enrollment flags in search results', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $enrolled = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Enrolled Course'],
        'status' => 3,
        'is_published' => true,
    ]);
    $open = Course::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Open Course'],
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyVideoToCourse($enrolled, $center);
    attachReadyVideoToCourse($open, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $enrolled->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search?search=Course');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_enrolled', true)
        ->assertJsonPath('data.1.is_enrolled', false);
});

it('falls back to latest courses when no recent views exist', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $older = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'created_at' => now()->subDays(5),
    ]);
    $latest = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'created_at' => now()->subDay(),
    ]);

    attachReadyVideoToCourse($older, $center);
    attachReadyVideoToCourse($latest, $center);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/search');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $latest->id);
});
