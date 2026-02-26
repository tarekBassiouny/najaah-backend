<?php

declare(strict_types=1);

use App\Models\Category;
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
    $branded = Center::factory()->create(['type' => 1]);

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

    $response->assertOk();

    $centers = collect($response->json('data'));
    expect($centers->pluck('id')->all())->toContain($unbranded->id);
    expect($centers->pluck('id')->all())->not->toContain($branded->id);
    expect($centers->firstWhere('id', $unbranded->id)['theme']['primary'] ?? null)->toBe('#123456');
    expect($centers->firstWhere('id', $unbranded->id))->toHaveKeys(['courses', 'courses_meta']);
});

it('does not list inactive unbranded centers', function (): void {
    $inactiveCenter = Center::factory()->create([
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

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($activeCenter->id);
    expect($ids)->not->toContain($inactiveCenter->id);
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

it('returns up to five courses per center in center list with courses meta', function (): void {
    $center = Center::factory()->create([
        'type' => 0,
        'name_translations' => ['en' => 'Capped Courses Center'],
    ]);
    $category = Category::factory()->create(['center_id' => $center->id]);
    $creator = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    foreach (range(1, 6) as $index) {
        $course = Course::factory()->create([
            'center_id' => $center->id,
            'status' => 3,
            'is_published' => true,
            'title_translations' => ['en' => 'Course '.$index],
            'category_id' => $category->id,
            'created_by' => $creator->id,
        ]);
        attachReadyCenterCourseVideo($course, $center);
    }

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers?search=Capped%20Courses%20Center&per_page=100');
    $response->assertOk();

    $centerPayload = collect($response->json('data'))->first(
        static fn (array $item): bool => (int) ($item['id'] ?? 0) === (int) $center->id
    );
    expect($centerPayload)->not->toBeNull();
    expect($centerPayload['courses'])->toHaveCount(5);
    expect($centerPayload['courses_meta']['total_courses'])->toBe(6);
    expect($centerPayload['courses_meta']['returned_courses'])->toBe(5);
    expect($centerPayload['courses_meta']['has_more_courses'])->toBeTrue();
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

it('filters center courses by category_id and is_featured', function (): void {
    $center = Center::factory()->create(['type' => 0]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $categoryA = Category::factory()->create(['center_id' => $center->id]);
    $categoryB = Category::factory()->create(['center_id' => $center->id]);

    $featuredCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'category_id' => $categoryA->id,
        'is_featured' => true,
    ]);
    $notFeaturedCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'category_id' => $categoryA->id,
        'is_featured' => false,
    ]);
    $otherCategoryCourse = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
        'category_id' => $categoryB->id,
        'is_featured' => true,
    ]);

    attachReadyCenterCourseVideo($featuredCourse, $center);
    attachReadyCenterCourseVideo($notFeaturedCourse, $center);
    attachReadyCenterCourseVideo($otherCategoryCourse, $center);

    $this->asApiUser($student);

    $categoryResponse = $this->apiGet('/api/v1/centers/'.$center->id.'?category_id='.$categoryA->id);
    $categoryResponse->assertOk();
    $categoryIds = collect($categoryResponse->json('data.courses'))->pluck('id')->all();
    expect($categoryIds)->toContain($featuredCourse->id, $notFeaturedCourse->id);
    expect($categoryIds)->not->toContain($otherCategoryCourse->id);

    $featuredResponse = $this->apiGet('/api/v1/centers/'.$center->id.'?is_featured=1');
    $featuredResponse->assertOk();
    $featuredIds = collect($featuredResponse->json('data.courses'))->pluck('id')->all();
    expect($featuredIds)->toContain($featuredCourse->id, $otherCategoryCourse->id);
    expect($featuredIds)->not->toContain($notFeaturedCourse->id);
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
