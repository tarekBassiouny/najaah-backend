<?php

use App\Actions\Courses\RemoveVideoFromCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('removes video via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $expectation = $service->shouldReceive('removeVideo');
    $expectation->once()->with($course, 3);

    $action = new RemoveVideoFromCourseAction($service);

    $action->execute($course, 3);
    expect(true)->toBeTrue();
});
