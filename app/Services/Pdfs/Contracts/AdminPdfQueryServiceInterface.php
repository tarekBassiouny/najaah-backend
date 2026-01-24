<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminPdfQueryServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Pdf>
     */
    public function paginateForCenter(User $admin, Center $center, int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
