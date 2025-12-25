<?php

use App\Actions\Courses\ShowCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;

uses()->group('course', 'actions', 'admin');

it('shows course via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $expectation = $service->shouldReceive('find');
    $expectation->once()->with(1, $actor)->andReturn($course);

    $action = new ShowCourseAction($service);

    $result = $action->execute($actor, 1);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
