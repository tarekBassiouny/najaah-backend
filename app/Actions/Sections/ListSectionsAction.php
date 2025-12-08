<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Support\Collection;

class ListSectionsAction
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    /**
     * @return Collection<int, Section>
     */
    public function execute(int $courseId): Collection
    {
        return $this->sectionService->listForCourse($courseId);
    }
}
