<?php

use App\Actions\Courses\ReorderSectionsAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

uses()->group('course', 'actions', 'admin', 'sections');

it('reorders sections via service', function (): void {
    $course = new Course;
    $actor = new User;
    $orderedIds = [1, 2, 3];

    /** @var Mockery\MockInterface&CourseStructureServiceInterface $service */
    $service = \Mockery::mock(CourseStructureServiceInterface::class);
    $expectation = $service->shouldReceive('reorderSections');
    $expectation->once()->with($course, $orderedIds, $actor);

    $action = new ReorderSectionsAction($service);

    $action->execute($actor, $course, $orderedIds);
    expect(true)->toBeTrue();
});
