<?php

declare(strict_types=1);

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Sections\SectionStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('sections', 'services');

it('blocks attaching videos to deleted sections', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $section->delete();

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
        'created_by' => $admin->id,
    ]);

    $service = app(SectionStructureService::class);

    $service->attachVideo($section, $video, $admin);
})->throws(AttachmentNotAllowedException::class);

it('attaches then detaches a ready video and keeps pivot ordering', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $uploadSession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => VideoUploadStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $uploadSession->id,
        'encoding_status' => VideoUploadStatus::Ready,
        'lifecycle_status' => VideoLifecycleStatus::Ready,
    ]);

    $service = app(SectionStructureService::class);
    $service->attachVideo($section, $video, $admin);

    $pivot = CourseVideo::query()
        ->where('course_id', $course->id)
        ->where('video_id', $video->id)
        ->first();

    expect($pivot)->not()->toBeNull();
    expect((int) $pivot->section_id)->toBe($section->id);
    expect((int) $pivot->order_index)->toBe(1);

    $service->detachVideo($section, $video, $admin);
    $pivot = $pivot?->fresh();

    expect($pivot)->not()->toBeNull();
    expect($pivot->section_id)->toBeNull();
});

it('attaches then detaches a ready pdf and keeps pivot ordering', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $uploadSession = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => \App\Enums\PdfUploadStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $uploadSession->id,
    ]);

    $service = app(SectionStructureService::class);
    $service->attachPdf($section, $pdf, $admin);

    $pivot = CoursePdf::query()
        ->where('course_id', $course->id)
        ->where('pdf_id', $pdf->id)
        ->first();

    expect($pivot)->not()->toBeNull();
    expect((int) $pivot->section_id)->toBe($section->id);
    expect((int) $pivot->order_index)->toBe(1);

    $service->detachPdf($section, $pdf, $admin);
    $pivot = $pivot?->fresh();

    expect($pivot)->not()->toBeNull();
    expect($pivot->section_id)->toBeNull();
});
