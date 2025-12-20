<?php

use App\Actions\Courses\ListCoursesAction;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

it('calls service paginate and returns paginator', function (): void {
    $actor = new User;
    /** @var Mockery\MockInterface&CourseServiceInterface $service */
    $service = \Mockery::mock(CourseServiceInterface::class);
    $paginator = \Mockery::mock(LengthAwarePaginator::class);
    $expectation = $service->shouldReceive('paginate');
    $expectation->once()->with(15, $actor)->andReturn($paginator);

    $action = new ListCoursesAction($service);

    $result = $action->execute($actor, 15);

    expect($result)->toBe($paginator);
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
