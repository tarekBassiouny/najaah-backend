<?php

use App\Actions\Courses\ToggleSectionVisibilityAction;
use App\Models\Section;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

uses()->group('course', 'actions', 'admin');

it('toggles section visibility via service', function (): void {
    $section = new Section;
    $actor = new User;

    /** @var Mockery\MockInterface&CourseStructureServiceInterface $service */
    $service = \Mockery::mock(CourseStructureServiceInterface::class);
    $expectation = $service->shouldReceive('toggleSectionVisibility');
    $expectation->once()->with($section, $actor)->andReturn($section);

    $action = new ToggleSectionVisibilityAction($service);

    $result = $action->execute($actor, $section);

    expect($result)->toBe($section);
    expect($result)->toBeInstanceOf(Section::class);
});
