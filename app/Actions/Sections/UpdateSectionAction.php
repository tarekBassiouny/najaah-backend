<?php

declare(strict_types=1);

namespace App\Actions\Sections;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Section;
use App\Models\User;
use App\Services\Sections\Contracts\SectionServiceInterface;

class UpdateSectionAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Section $section, array $data): Section
    {
        $data = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
        ], [
            'title_translations' => $section->title_translations ?? [],
            'description_translations' => $section->description_translations ?? [],
        ]);

        return $this->sectionService->update($section, $data, $actor);
    }
}
