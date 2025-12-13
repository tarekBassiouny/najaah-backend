<?php

declare(strict_types=1);

namespace App\Services\Centers\Contracts;

use App\Models\Center;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CenterServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Center>
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data): Center;

    /** @param array<string, mixed> $data */
    public function update(Center $center, array $data): Center;

    public function delete(Center $center): void;

    public function restore(int $id): ?Center;
}
