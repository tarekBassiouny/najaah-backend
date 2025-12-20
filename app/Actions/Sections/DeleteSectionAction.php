<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Models\User;
use App\Services\Sections\Contracts\SectionServiceInterface;

class DeleteSectionAction
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    public function execute(User $actor, Section $section): void
    {
        $this->sectionService->delete($section, $actor);
    }
}
