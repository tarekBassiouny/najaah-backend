<?php

declare(strict_types=1);

namespace App\Services\Pdfs\Contracts;

use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;

interface PdfServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Center $center, User $admin, array $data): Pdf;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Pdf $pdf, User $admin, array $data): Pdf;

    public function delete(Pdf $pdf, User $admin): void;
}
