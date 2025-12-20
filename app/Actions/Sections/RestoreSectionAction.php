<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Models\User;
use App\Services\Sections\Contracts\SectionServiceInterface;

class RestoreSectionAction
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    public function execute(User $actor, Section $section): Section
    {
        return $this->sectionService->restore($section, $actor);
    }
}
