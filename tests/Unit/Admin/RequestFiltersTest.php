<?php

declare(strict_types=1);

use App\Http\Requests\Admin\Centers\ListCentersRequest;
use App\Http\Requests\Admin\Courses\ListCoursesRequest;
use App\Http\Requests\Admin\Enrollments\ListEnrollmentsRequest;
use Tests\TestCase;

uses(TestCase::class);

it('builds center filters with trimmed values and booleans', function (): void {
    $request = ListCentersRequest::create('/admin/centers', 'GET', [
        'page' => '2',
        'per_page' => '25',
        'search' => '  Academy ',
        'is_featured' => '0',
        'created_from' => '2025-01-01',
        'created_to' => '2025-01-31',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->page)->toBe(2)
        ->and($filters->perPage)->toBe(25)
        ->and($filters->search)->toBe('Academy')
        ->and($filters->isFeatured)->toBeFalse()
        ->and($filters->createdFrom)->toBe('2025-01-01')
        ->and($filters->createdTo)->toBe('2025-01-31');
});

it('builds course filters with empty search as null', function (): void {
    $request = ListCoursesRequest::create('/admin/courses', 'GET', [
        'page' => '3',
        'per_page' => '10',
        'category_id' => '5',
        'primary_instructor_id' => '7',
        'search' => '   ',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->page)->toBe(3)
        ->and($filters->perPage)->toBe(10)
        ->and($filters->categoryId)->toBe(5)
        ->and($filters->primaryInstructorId)->toBe(7)
        ->and($filters->search)->toBeNull();
});

it('builds enrollment filters with trimmed status', function (): void {
    $request = ListEnrollmentsRequest::create('/admin/enrollments', 'GET', [
        'status' => ' ACTIVE ',
        'center_id' => '4',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->status)->toBe('ACTIVE')
        ->and($filters->centerId)->toBe(4);
});
