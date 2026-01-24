<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Models\Course;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface PdfAccessServiceInterface
{
    public function download(User $student, Course $course, int $pdfId): StreamedResponse;

    /**
     * @return array{url: string, expires_in: int}
     */
    public function signedUrl(User $student, Course $course, int $pdfId, int $expiresInSeconds): array;
}
