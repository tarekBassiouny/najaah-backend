<?php

use App\Actions\Courses\DeleteCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('deletes course via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->shouldReceive('delete')->once()->with($course);

    $action = new DeleteCourseAction($service);

    $action->execute($course);
    expect(true)->toBeTrue();
});
