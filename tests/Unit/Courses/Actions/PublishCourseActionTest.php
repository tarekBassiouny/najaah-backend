<?php

use App\Actions\Courses\PublishCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;

it('publishes course via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseWorkflowServiceInterface $service */
    $service = \Mockery::mock(CourseWorkflowServiceInterface::class);
    $expectation = $service->shouldReceive('publishCourse');
    $expectation->once()->with($course)->andReturn($course);

    $action = new PublishCourseAction($service);

    $result = $action->execute($course);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
