<?php

declare(strict_types=1);

namespace App\Services\Education\Contracts;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface GradeServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Grade>
     */
    public function paginateForCenter(User $admin, int $centerId, array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Grade>
     */
    public function lookupForCenter(User $admin, int $centerId, array $filters, bool $activeOnly = false): Collection;

    /** @param array<string, mixed> $data */
    public function createForCenter(User $admin, int $centerId, array $data): Grade;

    /** @param array<string, mixed> $data */
    public function updateForCenter(User $admin, int $centerId, Grade $grade, array $data): Grade;

    public function deleteForCenter(User $admin, int $centerId, Grade $grade): void;

    public function existsAndActive(int $gradeId, int $centerId): bool;
}
