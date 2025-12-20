<?php

use App\Actions\Courses\RemovePdfFromCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('removes pdf via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $expectation = $service->shouldReceive('removePdf');
    $expectation->once()->with($course, 9, $actor);

    $action = new RemovePdfFromCourseAction($service);

    $action->execute($actor, $course, 9);
    expect(true)->toBeTrue();
});
