<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface AdminAuthServiceInterface
{
    /**
     * @return array{
     *     user: User,
     *     token: string|null,
     *     requires_password_reset: bool,
     *     center_access_valid: bool,
     *     api_scope_valid: bool
     * }|null
     */
    public function login(string $email, string $password, ?int $resolvedCenterId = null): ?array;
}
