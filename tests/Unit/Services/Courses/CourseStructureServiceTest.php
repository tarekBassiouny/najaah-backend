<?php

use App\Models\Center;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseStructureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class)->group('course', 'services', 'content', 'admin');

it('adds section to course', function (): void {
    $service = new CourseStructureService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    $section = $service->addSection($course, [
        'title_translations' => ['en' => 'Section 1'],
    ], $actor);

    expect($section)->toBeInstanceOf(Section::class);
});

it('reorders sections', function (): void {
    $service = new CourseStructureService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $actor = User::factory()->create(['center_id' => $course->center_id]);
    $sections = Section::factory()->count(2)->create(['course_id' => $course->id]);
    $ordered = $sections->pluck('id')->reverse()->values()->all();

    $service->reorderSections($course, $ordered, $actor);

    $course->refresh();
    $reloaded = $course->sections()->orderBy('order_index')->get();
    expect($reloaded->pluck('id')->all())->toBe($ordered);
});

it('toggles visibility', function (): void {
    $service = new CourseStructureService(new CenterScopeService, new AuditLogService);
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id, 'visible' => true]);
    $section->loadMissing('course');
    $actor = User::factory()->create(['center_id' => $section->course->center_id]);
    $updated = $service->toggleSectionVisibility($section, $actor);
    expect($updated->visible)->toBeFalse();
});
