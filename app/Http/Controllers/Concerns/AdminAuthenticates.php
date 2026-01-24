<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

trait AdminAuthenticates
{
    protected function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }

    protected function notFound(string $message = 'Resource not found.'): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message,
            ],
        ], 404));
    }
}
