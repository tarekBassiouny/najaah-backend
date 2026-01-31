<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Support\ErrorCodes;
use Illuminate\Validation\ValidationException;

class StudentAccessService
{
    /**
     * @param  array<string, array<int, string>>|null  $validationErrors
     */
    public function assertStudent(
        User $user,
        ?string $message = null,
        ?string $code = null,
        int $status = 403,
        ?array $validationErrors = null
    ): void {
        if ($user->is_student) {
            return;
        }

        if ($validationErrors !== null) {
            throw ValidationException::withMessages($validationErrors);
        }

        $message = $message ?? 'Only students can perform this action.';
        $code = $code ?? ErrorCodes::UNAUTHORIZED;

        throw new DomainException($message, $code, $status);
    }
}
