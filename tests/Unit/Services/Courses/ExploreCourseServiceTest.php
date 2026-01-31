<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\DomainException;
use App\Filters\Mobile\CourseFilters;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Courses\ExploreCourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('courses', 'mobile', 'explore');

it('filters not enrolled courses in explore service', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

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

    $service = new ExploreCourseService;
    $filters = new CourseFilters(page: 1, perPage: 15, categoryId: null, instructorId: null, enrolled: false, isFeatured: null, publishFrom: null, publishTo: null);

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($openCourse->id)
        ->and($items[0]->is_enrolled)->toBeFalse();
});

it('scopes explore to center for branded students', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);

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

    $service = new ExploreCourseService;
    $filters = new CourseFilters(page: 1, perPage: 15, categoryId: null, instructorId: null, enrolled: null, isFeatured: null, publishFrom: null, publishTo: null);

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($courseA->id);
});

it('scopes explore to unbranded centers for system students', function (): void {
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
    Course::factory()->create([
        'center_id' => $branded->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $service = new ExploreCourseService;
    $filters = new CourseFilters(page: 1, perPage: 15, categoryId: null, instructorId: null, enrolled: null, isFeatured: null, publishFrom: null, publishTo: null);

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($unbrandedCourse->id);
});

it('excludes unpublished courses from explore service', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

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

    $service = new ExploreCourseService;
    $filters = new CourseFilters(page: 1, perPage: 15, categoryId: null, instructorId: null, enrolled: null, isFeatured: null, publishFrom: null, publishTo: null);

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($published->id);
});

it('filters courses by publish date range in explore service', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

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

    $service = new ExploreCourseService;
    $filters = new CourseFilters(
        page: 1,
        perPage: 15,
        categoryId: null,
        instructorId: null,
        enrolled: null,
        isFeatured: null,
        publishFrom: now()->subDays(5)->toDateString(),
        publishTo: now()->toDateString()
    );

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($match->id);
});

it('excludes courses with non-ready videos in explore service', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

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
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
        'upload_session_id' => $readySession->id,
    ]);
    $pendingVideo = Video::factory()->create([
        'encoding_status' => VideoUploadStatus::Processing,
        'lifecycle_status' => VideoLifecycleStatus::Processing,
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

    $service = new ExploreCourseService;
    $filters = new CourseFilters(page: 1, perPage: 15, categoryId: null, instructorId: null, enrolled: null, isFeatured: null, publishFrom: null, publishTo: null);

    $paginator = $service->explore($student, $filters);
    $items = $paginator->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($readyCourse->id);
});

it('returns not found for unpublished courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => CourseStatus::Draft,
        'is_published' => false,
    ]);

    $service = new ExploreCourseService;

    $thrown = null;
    try {
        $service->show($student, $course);
    } catch (DomainException $exception) {
        $thrown = $exception;
    }

    expect($thrown)->not->toBeNull()
        ->and($thrown?->statusCode())->toBe(404);
});

it('denies system students from branded center courses', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $service = new ExploreCourseService;

    $thrown = null;
    try {
        $service->show($student, $course);
    } catch (DomainException $exception) {
        $thrown = $exception;
    }

    expect($thrown)->not->toBeNull()
        ->and($thrown?->statusCode())->toBe(403);
});
