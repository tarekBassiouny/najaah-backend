<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Section;
use App\Services\Sections\Contracts\SectionWorkflowServiceInterface;

class UpdateSectionWithStructureAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly SectionWorkflowServiceInterface $workflowService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $videos
     * @param  array<int, int>  $pdfs
     */
    public function execute(Section $section, array $data, array $videos = [], array $pdfs = []): Section
    {
        $data = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
        ], [
            'title_translations' => $section->title_translations ?? [],
            'description_translations' => $section->description_translations ?? [],
        ]);

        return $this->workflowService->updateWithStructure($section, $data, $videos, $pdfs);
    }
}
