<?php

declare(strict_types=1);

namespace App\Services\Videos\Contracts;

use App\Filters\Admin\VideoFilters;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminVideoQueryServiceInterface
{
    /** @return LengthAwarePaginator<Video> */
    public function paginate(User $admin, VideoFilters $filters): LengthAwarePaginator;

    /** @return LengthAwarePaginator<Video> */
    public function paginateForCenter(User $admin, Center $center, VideoFilters $filters): LengthAwarePaginator;
}
