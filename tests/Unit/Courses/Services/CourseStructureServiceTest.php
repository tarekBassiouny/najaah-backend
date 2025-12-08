<?php

use App\Models\Course;
use App\Models\Section;
use App\Services\Courses\CourseStructureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('adds section to course', function (): void {
    $service = new CourseStructureService;
    $course = Course::factory()->create();
    $section = $service->addSection($course, [
        'title_translations' => ['en' => 'Section 1'],
    ]);

    expect($section)->toBeInstanceOf(Section::class);
});

it('reorders sections', function (): void {
    $service = new CourseStructureService;
    $course = Course::factory()->create();
    $sections = Section::factory()->count(2)->create(['course_id' => $course->id]);
    $ordered = $sections->pluck('id')->reverse()->values()->all();

    $service->reorderSections($course, $ordered);

    $course->refresh();
    $reloaded = $course->sections()->orderBy('order_index')->get();
    expect($reloaded->pluck('id')->all())->toBe($ordered);
});

it('toggles visibility', function (): void {
    $service = new CourseStructureService;
    $section = Section::factory()->create(['visible' => true]);
    $updated = $service->toggleSectionVisibility($section);
    expect($updated->visible)->toBeFalse();
});
