<?php

use App\Actions\Courses\UpdateCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('updates course via service', function (): void {
    $course = new Course;
    $actor = new User;
    $data = ['title' => 'Updated', 'language' => 'en'];
    $expected = [
        'title' => 'Updated',
        'language' => 'en',
        'title_translations' => ['en' => 'Updated'],
    ];

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->shouldReceive('update')->once()->with($course, $expected, $actor)->andReturn($course);

    $action = new UpdateCourseAction($service);

    $result = $action->execute($actor, $course, $data);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
