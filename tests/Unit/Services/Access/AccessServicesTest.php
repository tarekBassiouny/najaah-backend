<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\PdfAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\Access\VideoAccessService;
use App\Support\ErrorCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->group('access', 'services');

function captureDomainException(callable $callback): ?DomainException
{
    $thrown = null;

    try {
        $callback();
    } catch (DomainException $exception) {
        $thrown = $exception;
    }

    return $thrown;
}

function assertDomainError(?DomainException $exception, int $status, string $code): void
{
    expect($exception)->not->toBeNull();
    expect($exception?->statusCode())->toBe($status)
        ->and($exception?->errorCode())->toBe($code);
}

it('allows active students', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'status' => UserStatus::Active->value,
    ]);

    $service = new StudentAccessService;
    $service->assertStudent($student);

    expect(true)->toBeTrue();
});

it('rejects inactive students', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'status' => UserStatus::Inactive->value,
    ]);

    $service = new StudentAccessService;
    $exception = captureDomainException(fn () => $service->assertStudent($student));

    assertDomainError($exception, 403, ErrorCodes::FORBIDDEN);
});

it('requires published courses for active enrollment assertions', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => CourseStatus::Draft->value,
        'is_published' => false,
    ]);
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now(),
    ]);

    $service = new EnrollmentAccessService;
    $exception = captureDomainException(fn () => $service->assertActiveEnrollment($student, $course));

    assertDomainError($exception, 404, ErrorCodes::NOT_FOUND);
});

it('allows playback readiness without center ownership checks', function (): void {
    $center = Center::factory()->create();
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'encoding_status' => VideoUploadStatus::Ready->value,
        'lifecycle_status' => VideoLifecycleStatus::Ready->value,
        'upload_session_id' => null,
    ]);

    $service = new VideoAccessService;
    $service->assertReadyForPlayback($video);

    expect(true)->toBeTrue();
});

it('still rejects pdf attachments that are not ready', function (): void {
    $center = Center::factory()->create();
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
    ]);

    $service = new PdfAccessService;

    expect(fn () => $service->assertReadyForAttachment($pdf))->toThrow(AttachmentNotAllowedException::class);
});
