<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;

class FindSectionAction
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    public function execute(int $id): ?Section
    {
        return $this->sectionService->find($id);
    }
}
