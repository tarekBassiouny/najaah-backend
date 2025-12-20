<?php

use App\Actions\Courses\RemoveVideoFromCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('removes video via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $expectation = $service->shouldReceive('removeVideo');
    $expectation->once()->with($course, 3, $actor);

    $action = new RemoveVideoFromCourseAction($service);

    $action->execute($actor, $course, 3);
    expect(true)->toBeTrue();
});
