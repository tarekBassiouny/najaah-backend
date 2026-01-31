<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Filters\Admin\PdfFilters;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminPdfQueryServiceInterface
{
    /** @return LengthAwarePaginator<Pdf> */
    public function paginateForCenter(User $admin, Center $center, PdfFilters $filters): LengthAwarePaginator;
}
