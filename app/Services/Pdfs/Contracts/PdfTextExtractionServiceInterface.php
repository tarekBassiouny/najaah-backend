<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Models\Pdf;

interface PdfTextExtractionServiceInterface
{
    public function extractText(Pdf $pdf): string;

    public function extractPageCount(Pdf $pdf): ?int;

    public function canExtract(Pdf $pdf): bool;
}
