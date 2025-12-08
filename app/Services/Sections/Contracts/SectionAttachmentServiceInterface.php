<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Pdf;
use App\Models\Section;
use App\Models\Video;

interface SectionAttachmentServiceInterface
{
    public function moveVideoToSection(Video $video, Section $section): void;

    public function movePdfToSection(Pdf $pdf, Section $section): void;

    public function isVideoAttached(Video $video, Section $section): bool;

    public function isPdfAttached(Pdf $pdf, Section $section): bool;
}
