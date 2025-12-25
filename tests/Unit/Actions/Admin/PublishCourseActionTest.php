<?php

use App\Actions\Courses\PublishCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;

uses()->group('course', 'actions', 'admin');

it('publishes course via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseWorkflowServiceInterface $service */
    $service = \Mockery::mock(CourseWorkflowServiceInterface::class);
    $expectation = $service->shouldReceive('publishCourse');
    $expectation->once()->with($course, $actor)->andReturn($course);

    $action = new PublishCourseAction($service);

    $result = $action->execute($actor, $course);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
