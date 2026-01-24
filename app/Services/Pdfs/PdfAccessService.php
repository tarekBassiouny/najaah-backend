<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Models\Course;
use App\Models\Pivots\CoursePdf;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Pdfs\Contracts\PdfAccessServiceInterface;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PdfAccessService implements PdfAccessServiceInterface
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
        private readonly SettingsResolverServiceInterface $settingsResolverService,
        private readonly StorageServiceInterface $storageService
    ) {}

    public function download(User $student, Course $course, int $pdfId): StreamedResponse
    {
        $this->enforceEnrollment($student, $course);

        $pivot = CoursePdf::where('course_id', $course->id)
            ->where('pdf_id', $pdfId)
            ->whereNull('deleted_at')
            ->first();

        if ($pivot === null) {
            throw new NotFoundHttpException('PDF not found for this course.');
        }

        if ($pivot->visible === false) {
            throw new AccessDeniedHttpException('PDF download is not permitted.');
        }

        /** @var \App\Models\Pdf $pdf */
        $pdf = $pivot->pdf()->first();

        if (! $this->canDownload($student, $course, $pivot)) {
            throw new AccessDeniedHttpException('PDF download is not permitted.');
        }

        $path = $pdf->source_id;

        if (! is_string($path) || ! $this->storageService->exists($path)) {
            throw new NotFoundHttpException('PDF file not found.');
        }

        $filename = ($pdf->title ?? 'document').'.'.$pdf->file_extension;

        return $this->storageService->download($path, $filename);
    }

    /**
     * @return array{url:string,expires_in:int}
     */
    public function signedUrl(User $student, Course $course, int $pdfId, int $expiresInSeconds): array
    {
        $this->enforceEnrollment($student, $course);

        $pivot = CoursePdf::where('course_id', $course->id)
            ->where('pdf_id', $pdfId)
            ->whereNull('deleted_at')
            ->first();

        if ($pivot === null) {
            throw new NotFoundHttpException('PDF not found for this course.');
        }

        if ($pivot->visible === false) {
            throw new AccessDeniedHttpException('PDF download is not permitted.');
        }

        if (! $this->canDownload($student, $course, $pivot)) {
            throw new AccessDeniedHttpException('PDF download is not permitted.');
        }

        /** @var \App\Models\Pdf $pdf */
        $pdf = $pivot->pdf()->first();

        $path = $pdf->source_id;
        if (! is_string($path) || ! $this->storageService->exists($path)) {
            throw new NotFoundHttpException('PDF file not found.');
        }

        $expires = max(60, min(3600, $expiresInSeconds));

        return [
            'url' => $this->storageService->temporaryUrl($path, $expires),
            'expires_in' => $expires,
        ];
    }

    private function enforceEnrollment(User $student, Course $course): void
    {
        $enrollment = $this->enrollmentService->getActiveEnrollment($student, $course);

        if ($enrollment === null) {
            throw new AccessDeniedHttpException('Active enrollment is required.');
        }
    }

    private function canDownload(User $student, Course $course, CoursePdf $pivot): bool
    {
        if ($pivot->download_permission_override !== null) {
            return (bool) $pivot->download_permission_override;
        }

        $settings = $this->settingsResolverService->resolve($student, null, $course, $course->center);

        return (bool) ($settings['pdf_download_permission'] ?? false);
    }
}
