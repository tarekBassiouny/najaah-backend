<?php

declare(strict_types=1);

namespace App\Actions\Admin\Centers;

use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use App\Services\Centers\Contracts\CenterServiceInterface;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateCenterAction
{
    private const OWNER_ROLE_SLUG = 'center_owner';

    public function __construct(
        private readonly CenterServiceInterface $centerService,
        private readonly StoragePathResolver $pathResolver
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    public function execute(array $data): array
    {
        $centerData = [
            'slug' => $data['slug'],
            'type' => $data['type'],
            'name' => $data['name'],
            'logo_url' => $this->pathResolver->defaultCenterLogo(),
        ];

        if ((int) $data['type'] === Center::TYPE_BRANDED) {
            $centerData['api_key'] = $this->generateApiKey();
        }

        if (array_key_exists('tier', $data)) {
            $centerData['tier'] = $data['tier'];
        }

        if (array_key_exists('is_featured', $data)) {
            $centerData['is_featured'] = $data['is_featured'];
        }

        if (array_key_exists('branding_metadata', $data)) {
            $centerData['branding_metadata'] = $data['branding_metadata'];
        }

        $adminPayload = $data['admin'] ?? null;
        $ownerPayload = is_array($adminPayload) ? $adminPayload : null;

        $center = $this->centerService->create($centerData);

        return $this->runOnboarding($center, $ownerPayload);
    }

    private function generateApiKey(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = bin2hex(random_bytes(20));

            if (! Center::where('api_key', $key)->exists()) {
                return $key;
            }
        }

        throw new RuntimeException('Failed to generate unique API key.');
    }

    /**
     * @param  array<string, mixed>|null  $ownerPayload
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    private function runOnboarding(Center $center, ?array $ownerPayload): array
    {
        try {
            $this->markOnboardingStatus($center, Center::ONBOARDING_IN_PROGRESS);

            $owner = $this->resolveOwner($center, $ownerPayload);

            $this->markOnboardingStatus($center, Center::ONBOARDING_ACTIVE);
            $emailSent = $this->dispatchInvitation($center, $owner);

            return [
                'center' => $center->fresh(['setting']) ?? $center,
                'owner' => $owner->fresh() ?? $owner,
                'email_sent' => $emailSent,
            ];
        } catch (\Throwable $throwable) {
            $freshCenter = $center->fresh() ?? $center;
            $this->markOnboardingStatus($freshCenter, Center::ONBOARDING_FAILED);
            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>|null  $ownerPayload
     */
    private function resolveOwner(Center $center, ?array $ownerPayload): User
    {
        $currentOwner = $this->findCenterOwner($center);
        if ($currentOwner instanceof User) {
            $this->ensureRole($currentOwner, self::OWNER_ROLE_SLUG);

            return $currentOwner;
        }

        $payloadEmail = is_array($ownerPayload) ? ($ownerPayload['email'] ?? null) : null;
        if (is_string($payloadEmail) && $payloadEmail !== '') {
            $owner = User::where('email', $payloadEmail)->first();
            if ($owner instanceof User) {
                return $this->attachExistingOwner($owner, $center);
            }
        }

        return $this->createOwner($ownerPayload, $center);
    }

    private function findCenterOwner(Center $center): ?User
    {
        /** @var User|null $owner */
        $owner = $center->users()
            ->wherePivot('type', 'owner')
            ->first();

        return $owner;
    }

    private function attachExistingOwner(User $owner, Center $center): User
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

        $this->ensureRole($owner, self::OWNER_ROLE_SLUG);

        return $owner;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function createOwner(?array $payload, Center $center): User
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

        $this->ensureRole($owner, self::OWNER_ROLE_SLUG);

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

    private function dispatchInvitation(Center $center, User $owner): bool
    {
        if (! (bool) config('onboarding.async_enabled', true)) {
            return false;
        }

        if ($owner->invitation_sent_at === null) {
            SendAdminInvitationEmailJob::dispatch($center->id, $owner->id);

            return true;
        }

        return false;
    }

    private function markOnboardingStatus(Center $center, string $status): void
    {
        if ($center->onboarding_status === $status) {
            return;
        }

        $center->onboarding_status = $status;
        $center->save();
    }
}
