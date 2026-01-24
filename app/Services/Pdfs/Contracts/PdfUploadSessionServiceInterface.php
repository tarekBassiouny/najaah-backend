<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Models\Center;
use App\Models\PdfUploadSession;
use App\Models\User;

interface PdfUploadSessionServiceInterface
{
    public function initialize(Center $center, User $admin, string $originalFilename, ?int $fileSizeKb = null): PdfUploadSession;

    public function finalize(PdfUploadSession $session, User $admin, ?string $errorMessage = null): PdfUploadSession;
}
