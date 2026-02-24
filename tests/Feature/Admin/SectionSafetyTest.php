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
