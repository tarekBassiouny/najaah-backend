<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Models\Section;
use App\Models\User;
use App\Services\Sections\Contracts\SectionWorkflowServiceInterface;

class UnpublishSectionAction
{
    public function __construct(
        private readonly SectionWorkflowServiceInterface $workflowService
    ) {}

    public function execute(User $actor, Section $section): Section
    {
        return $this->workflowService->unpublish($actor, $section);
    }
}
