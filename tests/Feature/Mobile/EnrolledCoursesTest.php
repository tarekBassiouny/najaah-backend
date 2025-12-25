<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'enrolled');

function attachReadyCourseVideo(Course $course, Center $center): Video
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

it('returns only enrolled courses with explore response shape', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $enrolled = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $open = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCourseVideo($enrolled, $center);
    attachReadyCourseVideo($open, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $enrolled->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $enrolled->id)
        ->assertJsonPath('data.0.is_enrolled', true);
});

it('filters enrolled courses by category', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $category = Category::factory()->create(['center_id' => $center->id]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Course::factory()->create([
        'center_id' => $center->id,
        'category_id' => $category->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $other = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCourseVideo($match, $center);
    attachReadyCourseVideo($other, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $match->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $other->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled?category_id='.$category->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('filters enrolled courses by instructor', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $match->instructors()->syncWithoutDetaching([$instructor->id]);

    $other = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCourseVideo($match, $center);
    attachReadyCourseVideo($other, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $match->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $other->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled?instructor_id='.$instructor->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('paginates enrolled courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

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

    attachReadyCourseVideo($courseA, $center);
    attachReadyCourseVideo($courseB, $center);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $courseA->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $courseB->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('scopes enrolled courses for system students to unbranded centers', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $unbrandedCourse = Course::factory()->create([
        'center_id' => $unbranded->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $brandedCourse = Course::factory()->create([
        'center_id' => $branded->id,
        'status' => 3,
        'is_published' => true,
    ]);

    attachReadyCourseVideo($unbrandedCourse, $unbranded);
    attachReadyCourseVideo($brandedCourse, $branded);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $unbrandedCourse->id,
        'center_id' => $unbranded->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $brandedCourse->id,
        'center_id' => $branded->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $unbrandedCourse->id)
        ->assertJsonPath('data.0.is_enrolled', true);
});

it('returns empty list when student has no enrollments', function (): void {
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
    attachReadyCourseVideo($course, $center);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns unauthorized for non-student users', function (): void {
    $user = User::factory()->create([
        'is_student' => false,
    ]);

    $this->asApiUser($user);

    $response = $this->apiGet('/api/v1/courses/enrolled');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('returns validation errors for invalid filters', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled?per_page=0&page=0&category_id=bad&instructor_id=bad');

    $response->assertStatus(422);
});
