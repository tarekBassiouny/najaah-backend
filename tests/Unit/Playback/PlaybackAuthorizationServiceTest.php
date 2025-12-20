<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pivots\CourseVideo;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Playback\ConcurrencyService;
use App\Services\Playback\PlaybackAuthorizationService;
use App\Services\Playback\PlaybackSessionService;
use App\Services\Playback\ViewLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('playback');

function buildPlaybackService(array $overrides = []): PlaybackAuthorizationService
{
    $defaults = [
        'enrollmentService' => Mockery::mock(EnrollmentServiceInterface::class),
        'sessionService' => Mockery::mock(PlaybackSessionService::class),
        'deviceService' => Mockery::mock(DeviceServiceInterface::class),
        'viewLimitService' => Mockery::mock(ViewLimitService::class),
        'concurrencyService' => Mockery::mock(ConcurrencyService::class),
        'centerScopeService' => new CenterScopeService,
    ];

    $deps = array_merge($defaults, $overrides);

    return new PlaybackAuthorizationService(
        $deps['enrollmentService'],
        $deps['sessionService'],
        $deps['deviceService'],
        $deps['viewLimitService'],
        $deps['concurrencyService'],
        $deps['centerScopeService'],
    );
}

function assertPlaybackDenied(callable $callback, string $code): void
{
    try {
        $callback();
        expect(false)->toBeTrue();
    } catch (HttpResponseException $exception) {
        $response = $exception->getResponse();
        expect($response->getStatusCode())->toBe(403);
        expect($response->getData(true)['error']['code'] ?? null)->toBe($code);
    }
}

it('returns embed config when authorization succeeds', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 55,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'source_id' => 'bunny-1',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'device-1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->with($student, $course)
        ->andReturn($enrollment);

    $deviceService = Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('assertActiveDevice')
        ->once()
        ->with($student, 'device-1')
        ->andReturn($device);

    $concurrencyService = Mockery::mock(ConcurrencyService::class);
    $concurrencyService->shouldReceive('assertNoActiveSession')
        ->once()
        ->with($student, $device, $video);

    $viewLimitService = Mockery::mock(ViewLimitService::class);
    $viewLimitService->shouldReceive('assertWithinLimit')
        ->once()
        ->with($student, $video, $course, null);

    $sessionService = Mockery::mock(PlaybackSessionService::class);
    $sessionService->shouldReceive('startSession')
        ->once()
        ->andReturn(PlaybackSession::factory()->create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'device_id' => $device->id,
        ]));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
        'sessionService' => $sessionService,
        'deviceService' => $deviceService,
        'viewLimitService' => $viewLimitService,
        'concurrencyService' => $concurrencyService,
    ]);

    $result = $service->authorize($student, $course, $video, null, 'device-1');

    expect($result)->toHaveKey('embed_config')
        ->and($result['embed_config']['video_id'])->toBe('bunny-1')
        ->and($result['embed_config']['library_id'])->toBe(55);
});

it('denies playback without enrollment', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(null);

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'ENROLLMENT_REQUIRED'
    );
});

it('denies playback when center does not match', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $otherCenter = Center::factory()->create();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $otherCenter->id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'CENTER_MISMATCH'
    );
});

it('denies playback when video is not ready', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 2,
        'lifecycle_status' => 1,
        'source_id' => 'bunny-missing',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'VIDEO_NOT_READY'
    );
});

it('propagates device authorization failure', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'source_id' => 'bunny-device',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $deviceService = Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('assertActiveDevice')
        ->once()
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'DEVICE_MISMATCH',
                'message' => 'Device is not authorized for this user.',
            ],
        ], 403)));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
        'deviceService' => $deviceService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'DEVICE_MISMATCH'
    );
});

it('propagates concurrency failures', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'source_id' => 'bunny-concurrency',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'device-1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $deviceService = Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('assertActiveDevice')
        ->once()
        ->andReturn($device);

    $concurrencyService = Mockery::mock(ConcurrencyService::class);
    $concurrencyService->shouldReceive('assertNoActiveSession')
        ->once()
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'CONCURRENT_PLAYBACK',
                'message' => 'Another playback session is active.',
            ],
        ], 403)));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
        'deviceService' => $deviceService,
        'concurrencyService' => $concurrencyService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'CONCURRENT_PLAYBACK'
    );
});

it('propagates view limit failures', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 10,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'source_id' => 'bunny-view-limit',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'device-1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $deviceService = Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('assertActiveDevice')
        ->once()
        ->andReturn($device);

    $concurrencyService = Mockery::mock(ConcurrencyService::class);
    $concurrencyService->shouldReceive('assertNoActiveSession')
        ->once();

    $viewLimitService = Mockery::mock(ViewLimitService::class);
    $viewLimitService->shouldReceive('assertWithinLimit')
        ->once()
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VIEW_LIMIT_EXCEEDED',
                'message' => 'View limit exceeded.',
            ],
        ], 403)));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
        'deviceService' => $deviceService,
        'viewLimitService' => $viewLimitService,
        'concurrencyService' => $concurrencyService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'VIEW_LIMIT_EXCEEDED'
    );
});

it('denies playback when library id is missing', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => null,
    ]);
    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $center->id,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $course->center_id,
    ]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'source_id' => 'bunny-lib-missing',
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $enrollmentService = Mockery::mock(EnrollmentServiceInterface::class);
    $enrollmentService->shouldReceive('getActiveEnrollment')
        ->once()
        ->andReturn(Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => Enrollment::STATUS_ACTIVE,
        ]));

    $deviceService = Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('assertActiveDevice')
        ->once()
        ->andReturn(UserDevice::factory()->create([
            'user_id' => $student->id,
            'device_id' => 'device-1',
            'status' => UserDevice::STATUS_ACTIVE,
        ]));

    $concurrencyService = Mockery::mock(ConcurrencyService::class);
    $concurrencyService->shouldReceive('assertNoActiveSession')
        ->once();

    $viewLimitService = Mockery::mock(ViewLimitService::class);
    $viewLimitService->shouldReceive('assertWithinLimit')
        ->once();

    $sessionService = Mockery::mock(PlaybackSessionService::class);
    $sessionService->shouldReceive('startSession')
        ->once()
        ->andReturn(PlaybackSession::factory()->create([
            'user_id' => $student->id,
            'video_id' => $video->id,
        ]));

    $service = buildPlaybackService([
        'enrollmentService' => $enrollmentService,
        'sessionService' => $sessionService,
        'deviceService' => $deviceService,
        'viewLimitService' => $viewLimitService,
        'concurrencyService' => $concurrencyService,
    ]);

    assertPlaybackDenied(
        fn () => $service->authorize($student, $course, $video, null, 'device-1'),
        'LIBRARY_ID_MISSING'
    );
});
