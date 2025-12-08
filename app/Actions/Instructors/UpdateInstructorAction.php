<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;

class UpdateInstructorAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly InstructorServiceInterface $instructorService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Instructor $instructor, array $data): Instructor
    {
        $data = $this->normalizeTranslations($data, [
            'name_translations',
            'bio_translations',
            'title_translations',
        ], [
            'name_translations' => $instructor->name_translations ?? [],
            'bio_translations' => $instructor->bio_translations ?? [],
            'title_translations' => $instructor->title_translations ?? [],
        ]);

        return $this->instructorService->update($instructor, $data);
    }
}
