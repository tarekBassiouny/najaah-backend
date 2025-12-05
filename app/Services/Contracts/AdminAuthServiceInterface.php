<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;

interface AdminAuthServiceInterface
{
    /**
     * @return array{user: User, token: string}|null
     */
    public function login(string $email, string $password): ?array;

    public function logout(?User $user): void;

    public function me(?User $user): ?User;
}
