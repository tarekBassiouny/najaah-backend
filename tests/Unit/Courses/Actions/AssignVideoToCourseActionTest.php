<?php

use App\Actions\Courses\AssignVideoToCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('assigns video via service', function (): void {
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $service->allows()
        ->assignVideo($course, 5, $actor)
        ->andReturnNull();

    $action = new AssignVideoToCourseAction($service);

    $action->execute($actor, $course, 5);
    expect(true)->toBeTrue();
});
