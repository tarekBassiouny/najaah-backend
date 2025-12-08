<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;

class DeleteSectionAction
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    public function execute(Section $section): void
    {
        $this->sectionService->delete($section);
    }
}
