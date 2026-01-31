<?php

use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\PublishBlockedException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('course', 'services', 'workflow', 'admin');

it('publishes course when ready', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0, 'is_published' => false]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    Section::factory()->create(['course_id' => $course->id]);
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $actor->id,
        'upload_status' => 3,
        'progress_percent' => 100,
        'expires_at' => now()->addDay(),
    ]);
    $video = Video::factory()->create([
        'lifecycle_status' => VideoLifecycleStatus::Ready,
        'encoding_status' => VideoUploadStatus::Ready,
        'upload_session_id' => $session->id,
    ]);
    $video->update(['center_id' => $center->id]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $published = $service->publishCourse($course, $actor);

    expect($published->status)->toBe(CourseStatus::Published);
    expect($published->is_published)->toBeTrue();
});

it('throws when publishing without sections', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);

    $service->publishCourse($course, $actor);
})->throws(PublishBlockedException::class);

it('throws when publishing without visible sections', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    Section::factory()->create(['course_id' => $course->id, 'visible' => false]);

    $service->publishCourse($course, $actor);
})->throws(PublishBlockedException::class);

it('clones course with pivots', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['lifecycle_status' => VideoLifecycleStatus::Ready, 'encoding_status' => VideoUploadStatus::Ready, 'upload_session_id' => null]);
    $video->update(['center_id' => $center->id]);
    $pdf = Pdf::factory()->create();
    $pdf->update(['center_id' => $center->id]);
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

    $clone = $service->cloneCourse($course, $actor, []);

    expect($clone->id)->not()->toBe($course->id);
    expect($clone->sections)->not()->toBeNull();
    expect($clone->videos)->not()->toBeNull();
    expect($clone->pdfs)->not()->toBeNull();
});
