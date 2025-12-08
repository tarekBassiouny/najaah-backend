<?php

use App\Actions\Courses\CreateCourseAction;
use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

it('creates course via service', function (): void {
    $data = ['title' => 'New Course', 'language' => 'en'];
    $expected = [
        'title' => 'New Course',
        'language' => 'en',
        'title_translations' => ['en' => 'New Course'],
    ];
    $course = new Course;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->allows()
        ->create($expected)
        ->andReturn($course);

    $action = new CreateCourseAction($service);

    $result = $action->execute($data);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
