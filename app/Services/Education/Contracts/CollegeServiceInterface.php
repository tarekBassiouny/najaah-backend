<?php

declare(strict_types=1);

namespace App\Services\Education\Contracts;

use App\Models\College;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CollegeServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<College>
     */
    public function paginateForCenter(User $admin, int $centerId, array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, College>
     */
    public function lookupForCenter(User $admin, int $centerId, array $filters, bool $activeOnly = false): Collection;

    /** @param array<string, mixed> $data */
    public function createForCenter(User $admin, int $centerId, array $data): College;

    /** @param array<string, mixed> $data */
    public function updateForCenter(User $admin, int $centerId, College $college, array $data): College;

    public function deleteForCenter(User $admin, int $centerId, College $college): void;

    public function existsAndActive(int $collegeId, int $centerId): bool;
}
