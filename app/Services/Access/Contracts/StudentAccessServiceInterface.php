<?php

declare(strict_types=1);

namespace App\Services\Access\Contracts;

use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Validation\ValidationException;

interface StudentAccessServiceInterface
{
    /**
     * Assert that the user is an active student.
     *
     * @param  array<string, array<int, string>>|null  $validationErrors
     *
     * @throws DomainException
     * @throws ValidationException
     */
    public function assertStudent(
        User $user,
        ?string $message = null,
        ?string $code = null,
        int $status = 403,
        ?array $validationErrors = null
    ): void;
}
