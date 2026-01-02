<?php

declare(strict_types=1);

namespace App\Services\Centers\Contracts;

use App\Filters\Admin\CenterFilters as AdminCenterFilters;
use App\Filters\Mobile\CenterFilters;
use App\Models\Center;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CenterServiceInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Center;

    /** @param array<string, mixed> $data */
    public function update(Center $center, array $data): Center;

    public function delete(Center $center): void;

    public function restore(int $id): ?Center;

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdmin(AdminCenterFilters $filters): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listUnbranded(CenterFilters $filters): LengthAwarePaginator;

    /**
     * @return array{center: Center, courses: LengthAwarePaginator<\App\Models\Course>}
     */
    public function showWithCourses(User $student, Center $center, int $perPage = 15): array;
}
