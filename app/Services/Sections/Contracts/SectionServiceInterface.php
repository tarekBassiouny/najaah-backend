<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

interface SectionServiceInterface
{
    /** @return Collection<int, Section> */
    public function listForCourse(int $courseId, ?User $actor = null): Collection;

    public function find(int $id, ?User $actor = null): ?Section;

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Section;

    /** @param array<string, mixed> $data */
    public function update(Section $section, array $data, ?User $actor = null): Section;

    public function delete(Section $section, ?User $actor = null): void;

    public function restore(Section $section, ?User $actor = null): Section;

    public function reorder(Section $section, int $newIndex, ?User $actor = null): void;
}
