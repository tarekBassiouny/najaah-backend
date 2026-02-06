<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Pdfs\PdfAccessService;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('pdfs', 'services');

it('returns signed url for enrolled student with download permission', function (): void {
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(['center_id' => $student->center_id]);
    $pdf = Pdf::factory()->create(['center_id' => $course->center_id, 'source_id' => 'path/to/doc.pdf']);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->mock(EnrollmentServiceInterface::class, function (MockInterface $mock) use ($student, $course, $enrollment): void {
        $mock->shouldReceive('getActiveEnrollment')
            ->once()
            ->with($student, $course)
            ->andReturn($enrollment);
    });
    $this->mock(SettingsResolverServiceInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(['pdf_download_permission' => true]);
    });
    $this->mock(StorageServiceInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('exists')->once()->andReturn(true);
        $mock->shouldReceive('temporaryUrl')->once()->andReturn('https://signed.example/doc.pdf');
    });

    $service = app(PdfAccessService::class);
    $result = $service->signedUrl($student, $course, $pdf->id, 9000);

    expect($result['url'])->toBe('https://signed.example/doc.pdf');
    expect((int) $result['expires_in'])->toBe(3600);
});

it('downloads pdf when student is enrolled and allowed', function (): void {
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(['center_id' => $student->center_id]);
    $pdf = Pdf::factory()->create(['center_id' => $course->center_id, 'source_id' => 'path/to/doc.pdf']);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $stream = new StreamedResponse(static function (): void {});

    $this->mock(EnrollmentServiceInterface::class, function (MockInterface $mock) use ($student, $course, $enrollment): void {
        $mock->shouldReceive('getActiveEnrollment')
            ->once()
            ->with($student, $course)
            ->andReturn($enrollment);
    });
    $this->mock(SettingsResolverServiceInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(['pdf_download_permission' => true]);
    });
    $this->mock(StorageServiceInterface::class, function (MockInterface $mock) use ($stream): void {
        $mock->shouldReceive('exists')->once()->andReturn(true);
        $mock->shouldReceive('download')->once()->andReturn($stream);
    });

    $service = app(PdfAccessService::class);
    $response = $service->download($student, $course, $pdf->id);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});

it('denies signed url when download permission is disabled', function (): void {
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(['center_id' => $student->center_id]);
    $pdf = Pdf::factory()->create(['center_id' => $course->center_id, 'source_id' => 'path/to/doc.pdf']);
    CoursePdf::create([
        'course_id' => $course->id,
        'pdf_id' => $pdf->id,
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->mock(EnrollmentServiceInterface::class, function (MockInterface $mock) use ($enrollment): void {
        $mock->shouldReceive('getActiveEnrollment')
            ->once()
            ->andReturn($enrollment);
    });
    $this->mock(SettingsResolverServiceInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(['pdf_download_permission' => false]);
    });
    $this->mock(StorageServiceInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('exists')->never();
    });

    $service = app(PdfAccessService::class);
    $service->signedUrl($student, $course, $pdf->id, 300);
})->throws(AccessDeniedHttpException::class);
