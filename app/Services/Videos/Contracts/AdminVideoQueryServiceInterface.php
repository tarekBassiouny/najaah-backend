<?php

declare(strict_types=1);

namespace App\Services\Videos\Contracts;

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminVideoQueryServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Video>
     */
    public function paginate(User $admin, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Video>
     */
    public function paginateForCenter(User $admin, Center $center, int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
