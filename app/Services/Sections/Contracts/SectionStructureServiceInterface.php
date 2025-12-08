<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Pdf;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Support\Collection;

interface SectionStructureServiceInterface
{
    /** @return Collection<int, Video> */
    public function listVideos(Section $section): Collection;

    /** @return Collection<int, Pdf> */
    public function listPdfs(Section $section): Collection;

    public function attachVideo(Section $section, Video $video): void;

    public function detachVideo(Section $section, Video $video): void;

    public function attachPdf(Section $section, Pdf $pdf): void;

    public function detachPdf(Section $section, Pdf $pdf): void;

    /** @param array<int, int> $orderedIds */
    public function syncVideoOrder(Section $section, array $orderedIds): void;

    /** @param array<int, int> $orderedIds */
    public function syncPdfOrder(Section $section, array $orderedIds): void;
}
