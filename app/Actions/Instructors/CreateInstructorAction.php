<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;

class CreateInstructorAction
{
    use NormalizesTranslations;

    public function __construct(
        private readonly InstructorServiceInterface $instructorService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Instructor
    {
        $data = $this->normalizeTranslations($data, [
            'name_translations',
            'bio_translations',
            'title_translations',
        ]);

        return $this->instructorService->create($data);
    }
}
