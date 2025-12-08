<?php

use App\Actions\Courses\AssignPdfToCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;

it('assigns pdf via service', function (): void {
    $course = new Course;

    /** @var Mockery\MockInterface&CourseAttachmentServiceInterface $service */
    $service = \Mockery::mock(CourseAttachmentServiceInterface::class);
    $service->allows()
        ->assignPdf($course, 7)
        ->andReturnNull();

    $action = new AssignPdfToCourseAction($service);

    $action->execute($course, 7);
    expect(true)->toBeTrue();
});
