<?php

use App\Actions\Courses\AssignVideoToCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('assigns video via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $service->allows()
        ->assignVideo($course, 5)
        ->andReturnNull();

    $action = new AssignVideoToCourseAction($service);

    $action->execute($course, 5);
    expect(true)->toBeTrue();
});
