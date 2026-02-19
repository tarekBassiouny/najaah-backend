<?php

declare(strict_types=1);

use App\Http\Requests\Admin\Categories\ListCategoriesRequest;
use App\Http\Requests\Admin\Centers\ListCentersRequest;
use App\Http\Requests\Admin\Courses\ListCoursesRequest;
use App\Http\Requests\Admin\Devices\ListDeviceChangeRequestsRequest;
use App\Http\Requests\Admin\Enrollments\ListEnrollmentsRequest;
use App\Http\Requests\Admin\ExtraViews\ListExtraViewRequestsRequest;
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
    // center_id is now provided via route parameter, not query parameter
    $request = ListEnrollmentsRequest::create('/admin/centers/4/enrollments', 'GET', [
        'status' => ' ACTIVE ',
        'search' => '  Ahmed  ',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->status)->toBe('ACTIVE')
        ->and($filters->search)->toBe('Ahmed')
        ->and($filters->centerId)->toBeNull();
});

it('builds category filters with string booleans from query params', function (): void {
    $request = ListCategoriesRequest::create('/admin/categories', 'GET', [
        'page' => '1',
        'per_page' => '10',
        'is_active' => 'false',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->page)->toBe(1)
        ->and($filters->perPage)->toBe(10)
        ->and($filters->isActive)->toBeFalse()
        ->and($filters->parentId)->toBeNull();
});

it('builds extra view request filters with ids', function (): void {
    $request = ListExtraViewRequestsRequest::create('/admin/extra-view-requests', 'GET', [
        'center_id' => '2',
        'course_id' => '7',
        'video_id' => '11',
        'decided_by' => '9',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->centerId)->toBe(2)
        ->and($filters->courseId)->toBe(7)
        ->and($filters->videoId)->toBe(11)
        ->and($filters->decidedBy)->toBe(9);
});

it('builds device change filters with source and device ids', function (): void {
    $request = ListDeviceChangeRequestsRequest::create('/admin/device-change-requests', 'GET', [
        'center_id' => '4',
        'search' => '  Ahmed  ',
        'request_source' => 'ADMIN',
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $filters = $request->filters();

    expect($filters->centerId)->toBe(4)
        ->and($filters->search)->toBe('Ahmed')
        ->and($filters->requestSource)->toBe('ADMIN')
        ->and($filters->currentDeviceId)->toBe('old-device')
        ->and($filters->newDeviceId)->toBe('new-device');
});
