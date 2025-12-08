<?php

use App\Actions\Courses\UpdateCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('updates course via service', function (): void {
    $course = new Course;
    $data = ['title' => 'Updated', 'language' => 'en'];
    $expected = [
        'title' => 'Updated',
        'language' => 'en',
        'title_translations' => ['en' => 'Updated'],
    ];

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->shouldReceive('update')->once()->with($course, $expected)->andReturn($course);

    $action = new UpdateCourseAction($service);

    $result = $action->execute($course, $data);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
