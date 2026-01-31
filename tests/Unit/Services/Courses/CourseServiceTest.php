<?php

use App\Models\Course;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(TestCase::class, DatabaseTransactions::class)->group('course', 'services', 'content', 'admin');

it('creates and returns course', function (): void {
    $service = new CourseService(new CenterScopeService, new AuditLogService);
    $course = $service->create(Course::factory()->make()->toArray());

    expect($course)->toBeInstanceOf(Course::class);
    assertDatabaseHas('courses', ['id' => $course->id]);
});

it('paginates courses', function (): void {
    $service = new CourseService(new CenterScopeService, new AuditLogService);
    Course::factory()->count(2)->create();
    $paginator = $service->paginate(15);
    expect($paginator)->toBeObject();
    expect($paginator->total())->toBeGreaterThanOrEqual(2);
});

it('updates course', function (): void {
    $service = new CourseService(new CenterScopeService, new AuditLogService);
    $course = Course::factory()->create();
    $updated = $service->update($course, ['title' => 'Updated']);

    expect($updated)->toBeInstanceOf(Course::class);
    $fresh = Course::find($course->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh?->getRawOriginal('title_translations'))->toBe(json_encode('Updated'));
});

it('deletes course', function (): void {
    $service = new CourseService(new CenterScopeService, new AuditLogService);
    $course = Course::factory()->create();
    $service->delete($course);
    assertSoftDeleted('courses', ['id' => $course->id]);
});
