<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Services\Sections\SectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('sections', 'services');

it('creates section from legacy title fields and computes next order index', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    Section::factory()->create(['course_id' => $course->id, 'order_index' => 1]);

    $service = app(SectionService::class);
    $created = $service->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Legacy Title'],
        'description_translations' => ['en' => 'Legacy Desc'],
        'visible' => true,
    ], $admin);

    expect((int) $created->order_index)->toBe(2);
    expect(data_get($created->title_translations, 'en'))->toBe('Legacy Title');
    expect(data_get($created->description_translations, 'en'))->toBe('Legacy Desc');
});

it('restores a section and its soft deleted attachments', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);
    $pdf = Pdf::factory()->create(['center_id' => $center->id, 'created_by' => $admin->id]);

    $videoPivot = CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $pdfPivot = CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => $section->id,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);

    $section->delete();
    $videoPivot->delete();
    $pdfPivot->delete();

    $service = app(SectionService::class);
    $restored = $service->restore($section, $admin);

    expect($restored->deleted_at)->toBeNull();
    expect(CourseVideo::query()->find($videoPivot->id)?->deleted_at)->toBeNull();
    expect(CoursePdf::query()->find($pdfPivot->id)?->deleted_at)->toBeNull();
});
