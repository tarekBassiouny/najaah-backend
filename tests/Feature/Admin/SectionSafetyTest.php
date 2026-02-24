<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class)->group('sections', 'admin', 'safety');

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    $this->withoutMiddleware(Authenticate::class);
    $this->asAdmin();
});

it('returns not found for center mismatch', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $centerB->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$centerA->id}/courses/{$course->id}/sections/{$section->id}",
        $this->adminHeaders()
    );

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('blocks attaching video to section from another course', function (): void {
    $center = Center::factory()->create();
    $courseA = Course::factory()->create(['center_id' => $center->id]);
    $courseB = Course::factory()->create(['center_id' => $center->id]);
    $sectionB = Section::factory()->create(['course_id' => $courseB->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'created_by' => $courseA->created_by,
        'upload_session_id' => null,
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$courseA->id}/sections/{$sectionB->id}/videos",
        ['video_id' => $video->id],
        $this->adminHeaders()
    );

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('restores section attachments', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'created_by' => $course->created_by,
        'upload_session_id' => null,
    ]);
    $pdf = Pdf::factory()->create(['center_id' => $center->id, 'created_by' => $course->created_by]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $deleteResponse = $this->deleteJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/structure",
        [],
        $this->adminHeaders()
    );
    $deleteResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Section deleted successfully');

    $restoreResponse = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/restore",
        [],
        $this->adminHeaders()
    );
    $restoreResponse->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('course_video', [
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => $section->id,
        'deleted_at' => null,
    ]);
    $this->assertDatabaseHas('course_pdf', [
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'deleted_at' => null,
    ]);
});

it('includes section publish state in section list payload', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $published = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => true,
    ]);
    $unpublished = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => false,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true);

    $sections = collect($response->json('data'))->keyBy('id');
    expect($sections->get($published->id)['is_published'] ?? null)->toBeTrue();
    expect($sections->get($published->id)['visible'] ?? null)->toBeTrue();
    expect($sections->get($unpublished->id)['is_published'] ?? null)->toBeFalse();
    expect($sections->get($unpublished->id)['visible'] ?? null)->toBeFalse();
    expect($sections->get($published->id)['videos_count'] ?? null)->toBe(0);
    expect($sections->get($published->id)['pdfs_count'] ?? null)->toBe(0);
});

it('bulk publishes and unpublishes sections for the course', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $otherCourse = Course::factory()->create(['center_id' => $center->id]);

    $alreadyPublished = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => true,
    ]);
    $toPublish = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => false,
    ]);
    $otherCourseSection = Section::factory()->create([
        'course_id' => $otherCourse->id,
        'visible' => false,
    ]);

    $publishResponse = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/bulk-publish",
        [
            'section_ids' => [$alreadyPublished->id, $toPublish->id, $otherCourseSection->id],
        ],
        $this->adminHeaders()
    );

    $publishResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('sections', [
        'id' => $toPublish->id,
        'visible' => true,
    ]);

    $toUnpublish = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => true,
    ]);
    $alreadyUnpublished = Section::factory()->create([
        'course_id' => $course->id,
        'visible' => false,
    ]);

    $unpublishResponse = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/bulk-unpublish",
        [
            'section_ids' => [$toUnpublish->id, $alreadyUnpublished->id],
        ],
        $this->adminHeaders()
    );

    $unpublishResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 0);

    $this->assertDatabaseHas('sections', [
        'id' => $toUnpublish->id,
        'visible' => false,
    ]);
});

it('filters and searches sections with pagination on section list endpoint', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $matching = Section::factory()->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Intro Module', 'ar' => 'مقدمة'],
        'visible' => true,
        'order_index' => 1,
    ]);
    Section::factory()->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Advanced Module', 'ar' => 'متقدم'],
        'visible' => true,
        'order_index' => 2,
    ]);
    Section::factory()->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Intro Draft', 'ar' => 'مسودة مقدمة'],
        'visible' => false,
        'order_index' => 3,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections?search=Intro&is_published=1&page=1&per_page=1",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('data.0.id', $matching->id)
        ->assertJsonPath('data.0.is_published', true)
        ->assertJsonPath('data.0.videos_count', 0)
        ->assertJsonPath('data.0.pdfs_count', 0);
});

it('includes media counts and arrays in section details payload', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'created_by' => $course->created_by,
        'upload_session_id' => null,
    ]);
    $pdf = Pdf::factory()->create(['center_id' => $center->id, 'created_by' => $course->created_by]);

    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $section->id)
        ->assertJsonPath('data.videos_count', 1)
        ->assertJsonPath('data.pdfs_count', 1)
        ->assertJsonCount(1, 'data.videos')
        ->assertJsonCount(1, 'data.pdfs');
});
