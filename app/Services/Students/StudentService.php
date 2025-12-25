<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class StudentService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $user = User::create([
            'name' => (string) $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => (string) $data['phone'],
            'country_code' => (string) $data['country_code'],
            'center_id' => $data['center_id'] ?? null,
            'password' => Str::random(32),
            'is_student' => true,
            'status' => 1,
        ]);

        if (isset($data['center_id']) && is_numeric($data['center_id'])) {
            $centerId = (int) $data['center_id'];
            $user->centers()->syncWithoutDetaching([
                $centerId => ['type' => 'student'],
            ]);
        }

        return $user->refresh() ?? $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        $this->assertStudent($user);

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? null,
        ], static fn ($value): bool => $value !== null);

        $user->update($payload);

        return $user->refresh() ?? $user;
    }

    public function delete(User $user, ?User $actor = null): void
    {
        $this->assertStudent($user);

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $user->delete();
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_STUDENT',
                    'message' => 'User is not a student.',
                ],
            ], 422));
        }
    }
}
