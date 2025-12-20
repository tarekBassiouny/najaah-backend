<?php

declare(strict_types=1);

namespace App\Services\AdminUsers;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminUserService
{
    /**
     * @return LengthAwarePaginator<User>
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->where('is_student', false)
            ->with('roles')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $user = User::create([
            'name' => (string) $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => (string) $data['phone'],
            'password' => (string) $data['password'],
            'center_id' => $data['center_id'] ?? null,
            'is_student' => false,
            'status' => (int) ($data['status'] ?? 1),
        ]);

        if (isset($data['center_id']) && is_numeric($data['center_id'])) {
            $centerId = (int) $data['center_id'];
            $user->centers()->sync([$centerId => ['type' => 'admin']]);
        }

        return $user->refresh() ?? $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $this->assertAdminUser($user);

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? null,
            'status' => $data['status'] ?? null,
            'center_id' => $data['center_id'] ?? null,
        ], static fn ($value): bool => $value !== null);

        $user->update($payload);

        if (array_key_exists('center_id', $data)) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $user->centers()->sync($centerId !== null ? [$centerId => ['type' => 'admin']] : []);
        }

        return $user->refresh() ?? $user;
    }

    public function delete(User $user): void
    {
        $this->assertAdminUser($user);
        $user->delete();
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds): User
    {
        $this->assertAdminUser($user);
        $user->roles()->sync($roleIds);

        return $user->refresh() ?? $user;
    }

    private function assertAdminUser(User $user): void
    {
        if ($user->is_student) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ADMIN',
                    'message' => 'User is not an admin.',
                ],
            ], 422));
        }
    }
}
