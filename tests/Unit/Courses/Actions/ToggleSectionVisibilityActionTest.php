<?php

use App\Actions\Courses\ToggleSectionVisibilityAction;
use App\Models\Section;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;

it('toggles section visibility via service', function (): void {
    $section = new Section;

    /** @var Mockery\MockInterface&CourseStructureServiceInterface $service */
    $service = \Mockery::mock(CourseStructureServiceInterface::class);
    $expectation = $service->shouldReceive('toggleSectionVisibility');
    $expectation->once()->with($section)->andReturn($section);

    $action = new ToggleSectionVisibilityAction($service);

    $result = $action->execute($section);

    expect($result)->toBe($section);
    expect($result)->toBeInstanceOf(Section::class);
});
