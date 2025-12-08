<?php

use App\Actions\Courses\ReorderSectionsAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

it('reorders sections via service', function (): void {
    $course = new Course;
    $orderedIds = [1, 2, 3];

    /** @var Mockery\MockInterface&CourseStructureServiceInterface $service */
    $service = \Mockery::mock(CourseStructureServiceInterface::class);
    $expectation = $service->shouldReceive('reorderSections');
    $expectation->once()->with($course, $orderedIds);

    $action = new ReorderSectionsAction($service);

    $action->execute($course, $orderedIds);
    expect(true)->toBeTrue();
});
