<?php

use App\Enums\PdfUploadStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->group('course', 'services', 'content', 'admin');

it('assigns and removes video via pivot model', function (): void {
    $service = new CourseAttachmentService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $center->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $actor->id,
        'upload_status' => VideoUploadStatus::Ready,
        'expires_at' => now()->addDay(),
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
        'center_id' => $center->id,
        'created_by' => $actor->id,
    ]);

    $service->assignVideo($course, $video->id, $actor);
    expect(CourseVideo::where('course_id', $course->id)->where('video_id', $video->id)->exists())->toBeTrue();

    $service->removeVideo($course, $video->id, $actor);
    expect(CourseVideo::withTrashed()->where('course_id', $course->id)->where('video_id', $video->id)->first())->not()->toBeNull();
});

it('assigns and removes pdf via pivot model', function (): void {
    $service = new CourseAttachmentService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $center->id]);
    $session = PdfUploadSession::factory()->create([
        'center_id' => $center->id,
        'created_by' => $actor->id,
        'upload_status' => PdfUploadStatus::Ready,
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $actor->id,
        'upload_session_id' => $session->id,
    ]);

    $service->assignPdf($course, $pdf->id, $actor);
    expect(CoursePdf::where('course_id', $course->id)->where('pdf_id', $pdf->id)->exists())->toBeTrue();

    $service->removePdf($course, $pdf->id, $actor);
    expect(CoursePdf::withTrashed()->where('course_id', $course->id)->where('pdf_id', $pdf->id)->first())->not()->toBeNull();
});
