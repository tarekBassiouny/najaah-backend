<?php

declare(strict_types=1);

use App\Models\Category;
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

uses(RefreshDatabase::class)->group('courses', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);
    $this->withoutMiddleware(Authenticate::class);
    $this->asAdmin();
});

it('lists courses', function (): void {
    Course::factory()->count(2)->create();

    $response = $this->getJson('/admin/courses');

    $response->assertOk()->assertJsonPath('success', true);
});

it('creates course', function (): void {
    $payload = [
        'title' => 'Sample Course',
        'description' => 'A course description',
        'category_id' => Category::factory()->create()->id,
        'center_id' => Center::factory()->create()->id,
        'difficulty' => 'beginner',
        'language' => 'en',
        'price' => 0,
    ];

    $response = $this->postJson('/admin/courses', $payload);

    $response->assertCreated()->assertJsonPath('success', true);
    $response->assertJsonPath('data.title', 'Sample Course');
    $this->assertDatabaseHas('courses', ['title_translations->en' => 'Sample Course']);
});

it('shows course', function (): void {
    $course = Course::factory()->create();

    $response = $this->getJson("/admin/courses/{$course->id}");

    $response->assertOk()->assertJsonPath('data.id', $course->id);
});

it('updates course', function (): void {
    $course = Course::factory()->create();

    $response = $this->putJson("/admin/courses/{$course->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()->assertJsonPath('data.title', 'Updated Title');
    $this->assertDatabaseHas('courses', ['id' => $course->id, 'title_translations->en' => 'Updated Title']);
});

it('soft deletes course', function (): void {
    $course = Course::factory()->create();

    $response = $this->deleteJson("/admin/courses/{$course->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('adds section', function (): void {
    $course = Course::factory()->create();

    $response = $this->postJson("/admin/courses/{$course->id}/sections", [
        'title' => 'Section 1',
        'description' => 'Description',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('sections', ['course_id' => $course->id, 'title_translations->en' => 'Section 1']);
});

it('reorders sections', function (): void {
    $course = Course::factory()->create();
    $sections = Section::factory()->count(2)->create(['course_id' => $course->id]);
    $ordered = $sections->pluck('id')->reverse()->values()->all();

    $response = $this->putJson("/admin/courses/{$course->id}/sections/reorder", [
        'sections' => $ordered,
    ]);

    $response->assertOk()->assertJsonPath('success', true);
});

it('toggles section visibility', function (): void {
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id, 'visible' => true]);

    $response = $this->patchJson("/admin/courses/{$course->id}/sections/{$section->id}/visibility");

    $response->assertOk()->assertJsonPath('success', true);
    $section->refresh();
    expect($section->visible)->toBeFalse();
});

it('assigns video', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create([
        'created_by' => $course->created_by,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
    ]);

    $response = $this->postJson("/admin/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('removes video', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create(['created_by' => $course->created_by]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/admin/courses/{$course->id}/videos/{$video->id}");

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_video', ['course_id' => $course->id, 'video_id' => $video->id]);
});

it('assigns pdf', function (): void {
    $course = Course::factory()->create();
    $pdf = Pdf::factory()->create(['created_by' => $course->created_by]);

    $response = $this->postJson("/admin/courses/{$course->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('removes pdf', function (): void {
    $course = Course::factory()->create();
    $pdf = Pdf::factory()->create(['created_by' => $course->created_by]);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->deleteJson("/admin/courses/{$course->id}/pdfs/{$pdf->id}");

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('course_pdf', ['course_id' => $course->id, 'pdf_id' => $pdf->id]);
});

it('publishes course', function (): void {
    $course = Course::factory()->create(['status' => 0, 'is_published' => false]);
    Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'lifecycle_status' => 2,
        'encoding_status' => 3,
        'upload_session_id' => null,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $response = $this->postJson("/admin/courses/{$course->id}/publish");

    $response->assertOk()->assertJsonPath('success', true);
});

it('clones course', function (): void {
    $course = Course::factory()->create();

    $response = $this->postJson("/admin/courses/{$course->id}/clone", [
        'options' => [],
    ]);

    $response->assertCreated()->assertJsonPath('success', true);
});
