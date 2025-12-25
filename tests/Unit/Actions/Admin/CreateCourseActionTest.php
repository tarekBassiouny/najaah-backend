<?php

use App\Actions\Courses\CreateCourseAction;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;

uses()->group('course', 'actions', 'admin');

it('creates course via service', function (): void {
    $data = ['title' => 'New Course', 'language' => 'en'];
    $expected = [
        'title' => 'New Course',
        'language' => 'en',
        'title_translations' => ['en' => 'New Course'],
    ];
    $course = new Course;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $service->allows()
        ->create($expected, $actor)
        ->andReturn($course);

    $action = new CreateCourseAction($service);

    $result = $action->execute($actor, $data);

    expect($result)->toBe($course);
    expect($result)->toBeInstanceOf(Course::class);
});
