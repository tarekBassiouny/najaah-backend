<?php

use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Video;
use App\Services\Courses\CourseAttachmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

it('assigns and removes video via pivot model', function (): void {
    $service = new CourseAttachmentService;
    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $service->assignVideo($course, $video->id);
    expect(CourseVideo::where('course_id', $course->id)->where('video_id', $video->id)->exists())->toBeTrue();

    $service->removeVideo($course, $video->id);
    expect(CourseVideo::withTrashed()->where('course_id', $course->id)->where('video_id', $video->id)->first())->not()->toBeNull();
});

it('assigns and removes pdf via pivot model', function (): void {
    $service = new CourseAttachmentService;
    $course = Course::factory()->create();
    $pdf = Pdf::factory()->create();

    $service->assignPdf($course, $pdf->id);
    expect(CoursePdf::where('course_id', $course->id)->where('pdf_id', $pdf->id)->exists())->toBeTrue();

    $service->removePdf($course, $pdf->id);
    expect(CoursePdf::withTrashed()->where('course_id', $course->id)->where('pdf_id', $pdf->id)->first())->not()->toBeNull();
});
