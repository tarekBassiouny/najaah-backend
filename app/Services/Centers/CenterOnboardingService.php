<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Jobs\CreateCenterBunnyLibrary;
use App\Jobs\SendCenterOnboardingEmail;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CenterOnboardingService
{
    public function __construct(
        private readonly CenterServiceInterface $centerService
    ) {}

    /**
     * @param  array<string, mixed>  $centerData
     * @param  array<string, mixed>|null  $ownerPayload
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    public function onboard(array $centerData, ?User $existingOwner, ?array $ownerPayload, string $roleSlug): array
    {
        return DB::transaction(function () use ($centerData, $existingOwner, $ownerPayload, $roleSlug): array {
            $center = $this->centerService->create($centerData);

            if ($existingOwner instanceof User) {
                $owner = $this->attachExistingOwner($existingOwner, $center, $roleSlug);
            } else {
                $owner = $this->createOwner($ownerPayload, $center, $roleSlug);
            }

            SendCenterOnboardingEmail::dispatch($center->id, $owner->id)->afterCommit();
            CreateCenterBunnyLibrary::dispatch($center->id)->afterCommit();

            return [
                'center' => $center,
                'owner' => $owner->fresh() ?? $owner,
                'email_sent' => true,
            ];
        });
    }

    private function attachExistingOwner(User $owner, Center $center, string $roleSlug): User
    {
        if ($owner->is_student) {
            throw ValidationException::withMessages([
                'owner_user_id' => ['User must be an admin.'],
            ]);
        }

        if ($owner->email === null) {
            throw ValidationException::withMessages([
                'owner_user_id' => ['User must have an email address.'],
            ]);
        }

        if ($owner->center_id !== null && (int) $owner->center_id !== (int) $center->id) {
            throw ValidationException::withMessages([
                'owner_user_id' => ['User is already assigned to another center.'],
            ]);
        }

        if ($owner->center_id === null) {
            $owner->center_id = $center->id;
            $owner->save();
        }

        $owner->centers()->syncWithoutDetaching([
            $center->id => ['type' => 'owner'],
        ]);

        $this->ensureRole($owner, $roleSlug);

        return $owner;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function createOwner(?array $payload, Center $center, string $roleSlug): User
    {
        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'owner' => ['Owner payload is required.'],
            ]);
        }

        $owner = User::create([
            'name' => (string) $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => (string) ($payload['phone'] ?? '0000000000'),
            'password' => Str::random(32),
            'center_id' => $center->id,
            'is_student' => false,
            'status' => 1,
            'force_password_reset' => true,
        ]);

        $owner->centers()->sync([
            $center->id => ['type' => 'owner'],
        ]);

        $this->ensureRole($owner, $roleSlug);

        return $owner;
    }

    private function ensureRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();

        if (! $role instanceof Role) {
            throw ValidationException::withMessages([
                'role' => ['Invalid role supplied.'],
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
