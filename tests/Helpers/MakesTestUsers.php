<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\User;

trait MakesTestUsers
{
    protected function makeAdminUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['is_student' => false]);

        return $user;
    }

    protected function makeInstructorUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['is_student' => false]);

        return $user;
    }

    protected function makeStudentUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['is_student' => true]);

        return $user;
    }

    protected function makeApiUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'is_student' => true,
            'password' => 'secret123',
        ]);

        return $user;
    }
}
