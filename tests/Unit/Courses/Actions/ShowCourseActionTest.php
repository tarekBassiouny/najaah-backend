<?php

use App\Actions\Courses\ShowCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('shows course via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $expectation = $service->shouldReceive('find');
    $expectation->once()->with(1)->andReturn($course);

    $action = new ShowCourseAction($service);

    $result = $action->execute(1);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
