<?php

use App\Actions\Courses\DeleteCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('deletes course via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->shouldReceive('delete')->once()->with($course, $actor);

    $action = new DeleteCourseAction($service);

    $action->execute($actor, $course);
    expect(true)->toBeTrue();
});
