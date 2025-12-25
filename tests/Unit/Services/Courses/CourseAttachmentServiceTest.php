<?php

use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->group('course', 'services', 'content', 'admin');

it('assigns and removes video via pivot model', function (): void {
    $service = new CourseAttachmentService(new CenterScopeService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['encoding_status' => 3, 'lifecycle_status' => 2]);

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
    $pdf = Pdf::factory()->create();

    $service->assignPdf($course, $pdf->id, $actor);
    expect(CoursePdf::where('course_id', $course->id)->where('pdf_id', $pdf->id)->exists())->toBeTrue();

    $service->removePdf($course, $pdf->id, $actor);
    expect(CoursePdf::withTrashed()->where('course_id', $course->id)->where('pdf_id', $pdf->id)->first())->not()->toBeNull();
});
