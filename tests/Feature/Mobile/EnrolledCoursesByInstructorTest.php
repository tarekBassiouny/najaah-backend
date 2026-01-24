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

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'enrolled', 'by-instructor');

function createReadyVideo(Center $center): Video
{
    $readySession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    return Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $readySession->id,
    ]);
}

function attachVideoToCourse(Course $course, Video $video): void
{
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);
}

test('it returns enrolled courses grouped by instructor', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor1 = Instructor::factory()->create(['center_id' => $center->id]);
    $instructor2 = Instructor::factory()->create(['center_id' => $center->id]);

    $course1 = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $course1->instructors()->attach($instructor1->id);
    attachVideoToCourse($course1, createReadyVideo($center));

    $course2 = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $course2->instructors()->attach($instructor1->id);
    attachVideoToCourse($course2, createReadyVideo($center));

    $course3 = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $course3->instructors()->attach($instructor2->id);
    attachVideoToCourse($course3, createReadyVideo($center));

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course1->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course2->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course3->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'title',
                    'avatar_url',
                    'courses' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'is_enrolled',
                        ],
                    ],
                ],
            ],
        ]);

    $data = $response->json('data');
    $instructor1Data = collect($data)->firstWhere('id', $instructor1->id);
    $instructor2Data = collect($data)->firstWhere('id', $instructor2->id);

    expect($instructor1Data)->not->toBeNull();
    expect($instructor2Data)->not->toBeNull();
    expect(count($instructor1Data['courses']))->toBe(2);
    expect(count($instructor2Data['courses']))->toBe(1);
});

test('it returns empty list when student has no enrollments', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create(['center_id' => $center->id]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $course->instructors()->attach($instructor->id);
    attachVideoToCourse($course, createReadyVideo($center));

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('it only includes active enrollments', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $activeCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $activeCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($activeCourse, createReadyVideo($center));

    $cancelledCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $cancelledCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($cancelledCourse, createReadyVideo($center));

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $activeCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $cancelledCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_CANCELLED,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $instructorData = $response->json('data.0');
    expect(count($instructorData['courses']))->toBe(1);
    expect($instructorData['courses'][0]['id'])->toBe($activeCourse->id);
});

test('it scopes to student center for branded students', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $otherCenter = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $ownCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $ownCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($ownCourse, createReadyVideo($center));

    $otherCourse = Course::factory()->create([
        'center_id' => $otherCenter->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $otherCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($otherCourse, createReadyVideo($otherCenter));

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $ownCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $otherCourse->id,
        'center_id' => $otherCenter->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $instructorData = $response->json('data.0');
    expect(count($instructorData['courses']))->toBe(1);
    expect($instructorData['courses'][0]['id'])->toBe($ownCourse->id);
});

test('it scopes to unbranded centers for system students', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $instructor = Instructor::factory()->create(['center_id' => $unbranded->id]);

    $unbrandedCourse = Course::factory()->create([
        'center_id' => $unbranded->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $unbrandedCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($unbrandedCourse, createReadyVideo($unbranded));

    $brandedCourse = Course::factory()->create([
        'center_id' => $branded->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $brandedCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($brandedCourse, createReadyVideo($branded));

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

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $instructorData = $response->json('data.0');
    expect(count($instructorData['courses']))->toBe(1);
    expect($instructorData['courses'][0]['id'])->toBe($unbrandedCourse->id);
});

test('it returns unauthorized for non-student users', function (): void {
    $user = User::factory()->create([
        'is_student' => false,
    ]);

    $this->asApiUser($user);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

test('it returns unauthorized without authentication', function (): void {
    $response = $this->getJson('/api/v1/courses/enrolled/by-instructor');

    $response->assertStatus(403);
});

test('it excludes unpublished courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $publishedCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $publishedCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($publishedCourse, createReadyVideo($center));

    $unpublishedCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 0,
        'is_published' => false,
    ]);
    $unpublishedCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($unpublishedCourse, createReadyVideo($center));

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $publishedCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $unpublishedCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor');

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $instructorData = $response->json('data.0');
    expect(count($instructorData['courses']))->toBe(1);
    expect($instructorData['courses'][0]['id'])->toBe($publishedCourse->id);
});

test('it filters courses by category', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $category = Category::factory()->create(['center_id' => $center->id]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $matchCourse = Course::factory()->create([
        'center_id' => $center->id,
        'category_id' => $category->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $matchCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($matchCourse, createReadyVideo($center));

    $otherCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $otherCourse->instructors()->attach($instructor->id);
    attachVideoToCourse($otherCourse, createReadyVideo($center));

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $matchCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $otherCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor?category_id='.$category->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data');

    $instructorData = $response->json('data.0');
    expect(count($instructorData['courses']))->toBe(1);
    expect($instructorData['courses'][0]['id'])->toBe($matchCourse->id);
});

test('it returns validation error for invalid category_id', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/enrolled/by-instructor?category_id=invalid');

    $response->assertStatus(422);
});
