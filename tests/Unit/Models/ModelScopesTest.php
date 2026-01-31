<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\ExtraViewRequestStatus;
use App\Enums\UserDeviceStatus;
use App\Enums\VideoLifecycleStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\PlaybackSession;
use App\Models\UserDevice;
use App\Models\Video;

test('Center type constants are defined correctly', function (): void {
    expect(Center::TYPE_UNBRANDED)->toBe(CenterType::Unbranded);
    expect(Center::TYPE_BRANDED)->toBe(CenterType::Branded);
});

test('Course status constants are defined correctly', function (): void {
    expect(Course::STATUS_DRAFT)->toBe(CourseStatus::Draft);
    expect(Course::STATUS_PUBLISHED)->toBe(CourseStatus::Published);
});

test('Video lifecycle status constants are defined correctly', function (): void {
    expect(Video::LIFECYCLE_PROCESSING)->toBe(VideoLifecycleStatus::Processing);
    expect(Video::LIFECYCLE_READY)->toBe(VideoLifecycleStatus::Ready);
});

test('Enrollment status constants include pending', function (): void {
    expect(Enrollment::STATUS_ACTIVE)->toBe(EnrollmentStatus::Active);
    expect(Enrollment::STATUS_DEACTIVATED)->toBe(EnrollmentStatus::Deactivated);
    expect(Enrollment::STATUS_CANCELLED)->toBe(EnrollmentStatus::Cancelled);
    expect(Enrollment::STATUS_PENDING)->toBe(EnrollmentStatus::Pending);
});

test('UserDevice status constants are defined correctly', function (): void {
    expect(UserDevice::STATUS_ACTIVE)->toBe(UserDeviceStatus::Active);
    expect(UserDevice::STATUS_REVOKED)->toBe(UserDeviceStatus::Revoked);
    expect(UserDevice::STATUS_PENDING)->toBe(UserDeviceStatus::Pending);
});

test('ExtraViewRequest status constants are defined correctly', function (): void {
    expect(ExtraViewRequest::STATUS_PENDING)->toBe(ExtraViewRequestStatus::Pending);
    expect(ExtraViewRequest::STATUS_APPROVED)->toBe(ExtraViewRequestStatus::Approved);
    expect(ExtraViewRequest::STATUS_REJECTED)->toBe(ExtraViewRequestStatus::Rejected);
});

test('DeviceChangeRequest status constants are defined correctly', function (): void {
    expect(DeviceChangeRequest::STATUS_PENDING)->toBe(DeviceChangeRequestStatus::Pending);
    expect(DeviceChangeRequest::STATUS_APPROVED)->toBe(DeviceChangeRequestStatus::Approved);
    expect(DeviceChangeRequest::STATUS_REJECTED)->toBe(DeviceChangeRequestStatus::Rejected);
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
