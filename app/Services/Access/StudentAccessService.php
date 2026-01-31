<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\Access\Contracts\StudentAccessServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Validation\ValidationException;

class StudentAccessService implements StudentAccessServiceInterface
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
            if ((int) $user->status !== UserStatus::Active->value) {
                $message = $message ?? 'Student is not active.';
                $code = $code ?? ErrorCodes::FORBIDDEN;
                throw new DomainException($message, $code, $status);
            }

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
