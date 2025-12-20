<?php

use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('publishes course when ready', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0, 'is_published' => false]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['lifecycle_status' => 2, 'encoding_status' => 3, 'upload_session_id' => null]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $published = $service->publishCourse($course, $actor);

    expect($published->status)->toBe(3);
    expect($published->is_published)->toBeTrue();
});

it('throws when publishing without sections', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);

    $service->publishCourse($course, $actor);
})->throws(ValidationException::class);

it('clones course with pivots', function (): void {
    $service = new CourseWorkflowService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['lifecycle_status' => 2, 'encoding_status' => 3, 'upload_session_id' => null]);
    $pdf = Pdf::factory()->create();
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
