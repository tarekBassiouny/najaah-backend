<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Section;
use Illuminate\Support\Collection;

interface SectionServiceInterface
{
    /** @return Collection<int, Section> */
    public function listForCourse(int $courseId): Collection;

    public function find(int $id): ?Section;

    /** @param array<string, mixed> $data */
    public function create(array $data): Section;

    /** @param array<string, mixed> $data */
    public function update(Section $section, array $data): Section;

    public function delete(Section $section): void;

    public function restore(Section $section): Section;

    public function reorder(Section $section, int $newIndex): void;
}
