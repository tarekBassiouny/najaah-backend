<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;

class CreateSectionAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Section
    {
        $data = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
        ]);

        return $this->sectionService->create($data);
    }
}
