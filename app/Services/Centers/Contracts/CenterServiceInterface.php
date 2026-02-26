<?php

declare(strict_types=1);

namespace App\Services\Centers\Contracts;

use App\Filters\Admin\CenterFilters as AdminCenterFilters;
use App\Filters\Mobile\CenterFilters;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CenterServiceInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Center;

    /** @param array<string, mixed> $data */
    public function update(Center $center, array $data, ?User $actor = null): Center;

    public function delete(Center $center, ?User $actor = null): void;

    public function restore(int $id, ?User $actor = null): ?Center;

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdmin(AdminCenterFilters $filters): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdminOptions(AdminCenterFilters $filters): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listUnbranded(User $student, CenterFilters $filters): LengthAwarePaginator;

    /**
     * @return array{center: Center, courses: LengthAwarePaginator<Course>}
     */
    public function showWithCourses(
        User $student,
        Center $center,
        int $perPage = 15,
        ?int $categoryId = null,
        ?bool $isFeatured = null
    ): array;
}
