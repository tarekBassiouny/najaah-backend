<?php

declare(strict_types=1);

use App\Enums\MediaSourceType;
use App\Enums\PdfUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

function attachVideoToCourseForPublishing(Course $course, Video $video): void
{
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
}

function attachPdfToCourseForPublishing(Course $course, Pdf $pdf): void
{
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);
}

it('blocks publishing when any video is not ready', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertStatus(422);
});

it('allows publishing when videos are ready and latest session ready', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => 3,
        'progress_percent' => 100,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.status', 3);
});

it('allows publishing when the course only has a ready url video without an upload session', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'source_type' => MediaSourceType::Url,
        'source_url' => 'https://www.youtube.com/watch?v=abc123xyz',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => null,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 3);
});

it('allows publishing a mixed course when a ready uploaded video has an expired session', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $expiredSession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => 3,
        'progress_percent' => 100,
        'expires_at' => now()->subHour(),
    ]);

    $uploadedVideo = Video::factory()->create([
        'center_id' => $center->id,
        'source_type' => MediaSourceType::Upload,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $expiredSession->id,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $uploadedVideo);

    $urlVideo = Video::factory()->create([
        'center_id' => $center->id,
        'source_type' => MediaSourceType::Url,
        'source_url' => 'https://www.youtube.com/watch?v=abc123xyz',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => null,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $urlVideo);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 3);
});

it('allows publishing when a ready uploaded pdf has an expired session', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $expiredSession = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadStatus::Ready,
        'expires_at' => now()->subHour(),
    ]);

    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $expiredSession->id,
    ]);
    attachPdfToCourseForPublishing($course, $pdf);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 3);
});

it('blocks publishing when latest upload session is not ready', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => 1,
        'progress_percent' => 10,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/publish", [], $this->adminHeaders());

    $response->assertStatus(422);
});
