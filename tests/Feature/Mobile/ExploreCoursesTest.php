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

uses(RefreshDatabase::class, ApiTestHelper::class)->group('courses', 'mobile', 'explore');

it('lists courses for branded student center and marks enrollment', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $courseA = Course::factory()->create([
        'center_id' => $centerA->id,
        'status' => 3,
        'is_published' => true,
    ]);
    Course::factory()->create([
        'center_id' => $centerB->id,
        'status' => 3,
        'is_published' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $courseA->id,
        'center_id' => $centerA->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $courseA->id)
        ->assertJsonPath('data.0.is_enrolled', true);
});

it('lists only unbranded center courses for system students', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    Course::factory()->create([
        'center_id' => $unbranded->id,
        'status' => 3,
        'is_published' => true,
    ]);
    Course::factory()->create([
        'center_id' => $branded->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center.id', $unbranded->id);
});

it('applies filters and pagination', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $category = Category::factory()->create();
    $instructor = Instructor::factory()->create(['center_id' => $center->id]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $courseMatch = Course::factory()->create([
        'center_id' => $center->id,
        'category_id' => $category->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $courseMatch->instructors()->syncWithoutDetaching([$instructor->id]);

    Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $courseMatch->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore?category_id='.$category->id.'&instructor_id='.$instructor->id.'&enrolled=1&per_page=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $courseMatch->id)
        ->assertJsonPath('data.0.is_enrolled', true)
        ->assertJsonPath('meta.per_page', 1);
});

it('filters for not enrolled courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $enrolledCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $openCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $enrolledCourse->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore?enrolled=0');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $openCourse->id)
        ->assertJsonPath('data.0.is_enrolled', false);
});

it('returns validation errors for invalid pagination', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore?per_page=0');

    $response->assertStatus(422);
});

it('returns unauthorized for non-student users', function (): void {
    $user = User::factory()->create([
        'is_student' => false,
    ]);

    $this->asApiUser($user);

    $response = $this->apiGet('/api/v1/courses/explore');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('honors page parameter in pagination metadata', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Course::factory()->count(2)->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('excludes unpublished courses from explore', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => false,
    ]);
    $published = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id);
});

it('filters courses by publish date range', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'publish_at' => now()->subDays(2),
    ]);
    Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'publish_at' => now()->subDays(10),
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore?publish_from='.now()->subDays(5)->toDateString().'&publish_to='.now()->toDateString());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('excludes courses with non-ready videos from explore', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $readyCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $blockedCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $readySession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $readyVideo = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $readySession->id,
    ]);
    $pendingVideo = Video::factory()->create([
        'encoding_status' => 2,
        'lifecycle_status' => 1,
    ]);

    CourseVideo::create([
        'course_id' => $readyCourse->id,
        'video_id' => $readyVideo->id,
        'order_index' => 1,
        'visible' => true,
    ]);
    CourseVideo::create([
        'course_id' => $blockedCourse->id,
        'video_id' => $pendingVideo->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $readyCourse->id);
});
