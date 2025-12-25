<?php

use App\Models\Course;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\CourseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(TestCase::class, DatabaseTransactions::class)->group('course', 'services', 'content', 'admin');

it('creates and returns course', function (): void {
    $service = new CourseService(new CenterScopeService);
    $course = $service->create(Course::factory()->make()->toArray());

    expect($course)->toBeInstanceOf(Course::class);
    assertDatabaseHas('courses', ['id' => $course->id]);
});

it('paginates courses', function (): void {
    $service = new CourseService(new CenterScopeService);
    Course::factory()->count(2)->create();
    $paginator = $service->paginate(15);
    expect($paginator)->toBeObject();
    expect($paginator->total())->toBeGreaterThanOrEqual(2);
});

it('updates course', function (): void {
    $service = new CourseService(new CenterScopeService);
    $course = Course::factory()->create();
    $updated = $service->update($course, ['title_translations' => ['en' => 'Updated']]);

    expect($updated)->toBeInstanceOf(Course::class);
    assertDatabaseHas('courses', ['id' => $course->id, 'title_translations->en' => 'Updated']);
});

it('deletes course', function (): void {
    $service = new CourseService(new CenterScopeService);
    $course = Course::factory()->create();
    $service->delete($course);
    assertSoftDeleted('courses', ['id' => $course->id]);
});
