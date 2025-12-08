<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AdminTestHelper
{
    public ?string $adminToken;

    public function asAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'password' => 'secret123',
            'is_student' => false,
        ]);

        $this->adminToken = (string) Auth::guard('admin')->attempt([
            'email' => $admin->email,
            'password' => 'secret123',
            'is_student' => false,
        ]);

        return $admin;
    }

    public function adminHeaders(array $extra = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->adminToken,
        ], $extra);
    }
}
