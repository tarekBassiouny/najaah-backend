<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\Section;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Pdfs\PdfUploadSessionService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('blocks attaching non-ready video to course', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('blocks attaching video with non-ready upload session', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 1,
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos",
        ['video_id' => $video->id],
        $this->adminHeaders()
    );

    $response->assertStatus(422);
});

it('blocks attaching non-ready video to section', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/videos", [
        'video_id' => $video->id,
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('blocks attaching non-ready pdf to course', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadSessionService::STATUS_UPLOADING,
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('blocks attaching non-ready pdf to section', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadSessionService::STATUS_UPLOADING,
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('allows attaching ready video', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadService::STATUS_READY,
        'expires_at' => now()->addDay(),
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
    ]);

    $courseAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ], $this->adminHeaders());
    $courseAttach->assertCreated();

    $sectionAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/videos", [
        'video_id' => $video->id,
    ], $this->adminHeaders());
    $sectionAttach->assertCreated();
});

it('allows attaching ready pdf to course and section', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['created_by' => $admin->id, 'center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_status' => PdfUploadSessionService::STATUS_READY,
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
    ]);

    $courseAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ], $this->adminHeaders());
    $courseAttach->assertCreated();

    $sectionAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/courses/{$course->id}/sections/{$section->id}/pdfs", [
        'pdf_id' => $pdf->id,
    ], $this->adminHeaders());
    $sectionAttach->assertCreated();
});
