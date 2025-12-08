<?php

use App\Actions\Courses\AddSectionToCourseAction;
use App\Models\Course;
use App\Models\Section;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

it('adds section via service', function (): void {
    $course = new Course;
    $data = ['title' => 'Section 1'];
    $section = new Section;

    /** @var Mockery\MockInterface&CourseStructureServiceInterface $service */
    $service = \Mockery::mock(CourseStructureServiceInterface::class);
    $service->allows()
        ->addSection($course, [
            'title' => 'Section 1',
            'title_translations' => ['en' => 'Section 1'],
        ])
        ->andReturn($section);

    $action = new AddSectionToCourseAction($service);

    $result = $action->execute($course, $data);

    expect($result)->toBe($section);
    expect($result)->toBeInstanceOf(Section::class);
});
