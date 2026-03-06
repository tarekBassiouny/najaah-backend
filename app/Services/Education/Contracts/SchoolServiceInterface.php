<?php

declare(strict_types=1);

namespace App\Services\Education\Contracts;

use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SchoolServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<School>
     */
    public function paginateForCenter(User $admin, int $centerId, array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, School>
     */
    public function lookupForCenter(User $admin, int $centerId, array $filters, bool $activeOnly = false): Collection;

    /** @param array<string, mixed> $data */
    public function createForCenter(User $admin, int $centerId, array $data): School;

    /** @param array<string, mixed> $data */
    public function updateForCenter(User $admin, int $centerId, School $school, array $data): School;

    public function deleteForCenter(User $admin, int $centerId, School $school): void;

    public function existsAndActive(int $schoolId, int $centerId): bool;
}
