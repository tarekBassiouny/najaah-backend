<?php

use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Services\Courses\CourseWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('publishes course when ready', function (): void {
    $service = new CourseWorkflowService;
    $course = Course::factory()->create(['status' => 0, 'is_published' => false]);
    Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['lifecycle_status' => 2]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $published = $service->publishCourse($course);

    expect($published->status)->toBe(3);
    expect($published->is_published)->toBeTrue();
});

it('throws when publishing without sections', function (): void {
    $service = new CourseWorkflowService;
    $course = Course::factory()->create(['status' => 0]);

    $service->publishCourse($course);
})->throws(ValidationException::class);

it('clones course with pivots', function (): void {
    $service = new CourseWorkflowService;
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['lifecycle_status' => 2]);
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

    $clone = $service->cloneCourse($course, []);

    expect($clone->id)->not()->toBe($course->id);
    expect($clone->sections)->not()->toBeNull();
    expect($clone->videos)->not()->toBeNull();
    expect($clone->pdfs)->not()->toBeNull();
});
