<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Playback\ViewLimitService;
use App\Services\Requests\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('requests', 'services');

beforeEach(function (): void {
    $this->viewLimitService = Mockery::mock(ViewLimitService::class);
    $this->service = new RequestService($this->viewLimitService);
});

afterEach(function (): void {
    Mockery::close();
});

test('createEnrollmentRequest creates pending enrollment in transaction', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $this->service->createEnrollmentRequest($student, $center, $course, 'Test reason');

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_PENDING,
    ]);
});

test('createEnrollmentRequest prevents duplicate pending requests', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    // Create first pending enrollment
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_PENDING,
        'enrolled_at' => now(),
    ]);

    // Attempt to create duplicate should throw
    expect(fn () => $this->service->createEnrollmentRequest($student, $center, $course, null))
        ->toThrow(\App\Exceptions\DomainException::class, 'A pending enrollment request already exists.');
});

test('createEnrollmentRequest prevents request when already enrolled', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    // Create active enrollment
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    expect(fn () => $this->service->createEnrollmentRequest($student, $center, $course, null))
        ->toThrow(\App\Exceptions\DomainException::class, 'Student is already enrolled.');
});

test('createDeviceChangeRequest creates pending request in transaction', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'test-device-123',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $this->service->createDeviceChangeRequest($student, 'Need new phone');

    $this->assertDatabaseHas('device_change_requests', [
        'user_id' => $student->id,
        'current_device_id' => 'test-device-123',
        'status' => DeviceChangeRequest::STATUS_PENDING,
        'reason' => 'Need new phone',
    ]);
});

test('createDeviceChangeRequest prevents duplicate pending requests', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'test-device-123',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    // Create first pending request
    DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $center->id,
        'current_device_id' => 'test-device-123',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
        'reason' => 'First request',
    ]);

    expect(fn () => $this->service->createDeviceChangeRequest($student, 'Second request'))
        ->toThrow(\App\Exceptions\DomainException::class, 'A pending device change request already exists.');
});

test('createDeviceChangeRequest requires active device', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    expect(fn () => $this->service->createDeviceChangeRequest($student, 'No device'))
        ->toThrow(\App\Exceptions\DomainException::class, 'Active device required to request a change.');
});

test('createExtraViewRequest creates pending request in transaction', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    // Attach video to course
    $course->videos()->attach($video->id, [
        'order_index' => 1,
        'visible' => true,
    ]);

    // Create active enrollment
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    // Mock view limit to return 0 (no views remaining)
    $this->viewLimitService->shouldReceive('remaining')
        ->once()
        ->andReturn(0);

    $this->service->createExtraViewRequest($student, $center, $course, $video, 'Need more views');

    $this->assertDatabaseHas('extra_view_requests', [
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'status' => ExtraViewRequest::STATUS_PENDING,
        'reason' => 'Need more views',
    ]);
});

test('createExtraViewRequest prevents request when views remain', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $course->videos()->attach($video->id, [
        'order_index' => 1,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    // Mock view limit to return 5 (views remaining)
    $this->viewLimitService->shouldReceive('remaining')
        ->once()
        ->andReturn(5);

    expect(fn () => $this->service->createExtraViewRequest($student, $center, $course, $video, null))
        ->toThrow(\App\Exceptions\DomainException::class, 'Extra views are not allowed while views remain.');
});

test('only students can create requests', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    expect(fn () => $this->service->createEnrollmentRequest($admin, $center, $course, null))
        ->toThrow(\App\Exceptions\UnauthorizedException::class, 'Only students can perform this action.');
});
