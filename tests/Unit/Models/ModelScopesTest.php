<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\PlaybackSession;
use App\Models\UserDevice;
use App\Models\Video;

test('Center type constants are defined correctly', function (): void {
    expect(Center::TYPE_UNBRANDED)->toBe(0);
    expect(Center::TYPE_BRANDED)->toBe(1);
});

test('Course status constants are defined correctly', function (): void {
    expect(Course::STATUS_DRAFT)->toBe(0);
    expect(Course::STATUS_PUBLISHED)->toBe(3);
});

test('Video lifecycle status constants are defined correctly', function (): void {
    expect(Video::LIFECYCLE_PROCESSING)->toBe(1);
    expect(Video::LIFECYCLE_READY)->toBe(2);
});

test('Enrollment status constants include pending', function (): void {
    expect(Enrollment::STATUS_ACTIVE)->toBe(0);
    expect(Enrollment::STATUS_DEACTIVATED)->toBe(1);
    expect(Enrollment::STATUS_CANCELLED)->toBe(2);
    expect(Enrollment::STATUS_PENDING)->toBe(3);
});

test('UserDevice status constants are defined correctly', function (): void {
    expect(UserDevice::STATUS_ACTIVE)->toBe(0);
    expect(UserDevice::STATUS_REVOKED)->toBe(1);
    expect(UserDevice::STATUS_PENDING)->toBe(2);
});

test('ExtraViewRequest status constants are defined correctly', function (): void {
    expect(ExtraViewRequest::STATUS_PENDING)->toBe('PENDING');
    expect(ExtraViewRequest::STATUS_APPROVED)->toBe('APPROVED');
    expect(ExtraViewRequest::STATUS_REJECTED)->toBe('REJECTED');
});

test('DeviceChangeRequest status constants are defined correctly', function (): void {
    expect(DeviceChangeRequest::STATUS_PENDING)->toBe('PENDING');
    expect(DeviceChangeRequest::STATUS_APPROVED)->toBe('APPROVED');
    expect(DeviceChangeRequest::STATUS_REJECTED)->toBe('REJECTED');
});

test('Video model has readyForPlayback scope method', function (): void {
    $video = new Video;
    expect(method_exists($video, 'scopeReadyForPlayback'))->toBeTrue();
});

test('Course model has published scope method', function (): void {
    $course = new Course;
    expect(method_exists($course, 'scopePublished'))->toBeTrue();
});

test('Enrollment model has scope methods', function (): void {
    $enrollment = new Enrollment;
    expect(method_exists($enrollment, 'scopeActiveForUserAndCourse'))->toBeTrue();
    expect(method_exists($enrollment, 'scopeActive'))->toBeTrue();
    expect(method_exists($enrollment, 'scopePending'))->toBeTrue();
});

test('UserDevice model has scope methods', function (): void {
    $device = new UserDevice;
    expect(method_exists($device, 'scopeActive'))->toBeTrue();
    expect(method_exists($device, 'scopeActiveForUser'))->toBeTrue();
});

test('ExtraViewRequest model has pending scope method', function (): void {
    $request = new ExtraViewRequest;
    expect(method_exists($request, 'scopePending'))->toBeTrue();
});

test('DeviceChangeRequest model has pending scope method', function (): void {
    $request = new DeviceChangeRequest;
    expect(method_exists($request, 'scopePending'))->toBeTrue();
});

test('PlaybackSession model has scope methods', function (): void {
    $session = new PlaybackSession;
    expect(method_exists($session, 'scopeActive'))->toBeTrue();
    expect(method_exists($session, 'scopeForUser'))->toBeTrue();
    expect(method_exists($session, 'scopeExpired'))->toBeTrue();
    expect(method_exists($session, 'scopeFullPlaysForUserAndVideo'))->toBeTrue();
});
