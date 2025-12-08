<?php

use App\Actions\Courses\CloneCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;

it('clones course via service', function (): void {
    $course = new Course;
    $cloned = new Course;
    $options = ['include_sections' => true];

    /** @var Mockery\MockInterface&CourseWorkflowServiceInterface $service */
    $service = \Mockery::mock(CourseWorkflowServiceInterface::class);
    $service->allows()
        ->cloneCourse($course, $options)
        ->andReturn($cloned);

    $action = new CloneCourseAction($service);

    $result = $action->execute($course, $options);

    expect($result)->toBe($cloned);
    expect($result)->toBeInstanceOf(Course::class);
});
