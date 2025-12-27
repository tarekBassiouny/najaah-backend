<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Jobs\CreateBunnyLibraryJob;
use App\Jobs\ProcessCenterLogoJob;
use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\Centers\Contracts\CenterServiceInterface;
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
        $center = $this->centerService->create($centerData);

        return $this->runOnboarding($center, $existingOwner, $ownerPayload, $roleSlug);
    }

    /**
     * @param  array<string, mixed>|null  $ownerPayload
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    public function resume(Center $center, ?User $existingOwner, ?array $ownerPayload, string $roleSlug): array
    {
        $center->refresh();

        return $this->runOnboarding($center, $existingOwner, $ownerPayload, $roleSlug);
    }

    /**
     * @param  array<string, mixed>|null  $ownerPayload
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    private function runOnboarding(Center $center, ?User $existingOwner, ?array $ownerPayload, string $roleSlug): array
    {
        if ($center->onboarding_status === Center::ONBOARDING_ACTIVE) {
            $owner = $this->resolveExistingOwner($center, $existingOwner);
            $this->dispatchAsyncJobs($center, $owner);

            return [
                'center' => $center->fresh(['setting']) ?? $center,
                'owner' => $owner,
                'email_sent' => false,
            ];
        }

        try {
            $this->markOnboardingStatus($center, Center::ONBOARDING_IN_PROGRESS);
            $this->initializeSettings($center);
            $this->applyStorageDefaults($center);

            $owner = $this->resolveOwner($center, $existingOwner, $ownerPayload, $roleSlug);

            $this->markOnboardingStatus($center, Center::ONBOARDING_ACTIVE);
            $this->dispatchAsyncJobs($center, $owner);

            return [
                'center' => $center->fresh(['setting']) ?? $center,
                'owner' => $owner->fresh() ?? $owner,
                'email_sent' => false,
            ];
        } catch (\Throwable $throwable) {
            $freshCenter = $center->fresh() ?? $center;
            $this->markOnboardingStatus($freshCenter, Center::ONBOARDING_FAILED);
            throw $throwable;
        }
    }

    private function initializeSettings(Center $center): void
    {
        CenterSetting::firstOrCreate([
            'center_id' => $center->id,
        ], [
            'settings' => [],
        ]);
    }

    private function applyStorageDefaults(Center $center): void
    {
        $updates = [];

        if (! is_string($center->storage_driver) || $center->storage_driver === '') {
            $updates['storage_driver'] = 'spaces';
        }

        $storageRoot = $center->getRawOriginal('storage_root');
        if (! is_string($storageRoot) || $storageRoot === '') {
            $updates['storage_root'] = 'centers/'.$center->id;
        }

        if ($updates !== []) {
            $center->fill($updates);
            $center->save();
        }
    }

    private function dispatchAsyncJobs(Center $center, User $owner): void
    {
        if (! (bool) config('onboarding.async_enabled', true)) {
            return;
        }

        if (! is_numeric($center->bunny_library_id)) {
            CreateBunnyLibraryJob::dispatch($center->id);
        }

        if ($owner->invitation_sent_at === null) {
            SendAdminInvitationEmailJob::dispatch($center->id, $owner->id);
        }

        if ($this->shouldProcessLogo($center)) {
            ProcessCenterLogoJob::dispatch($center->id, (string) $center->logo_url);
        }
    }

    private function shouldProcessLogo(Center $center): bool
    {
        if (! is_string($center->logo_url) || $center->logo_url === '') {
            return false;
        }

        $metadata = is_array($center->branding_metadata) ? $center->branding_metadata : [];
        $source = $metadata['logo_source'] ?? null;
        $processedAt = $metadata['logo_processed_at'] ?? null;

        if ($source === $center->logo_url && is_string($processedAt) && $processedAt !== '') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $ownerPayload
     */
    private function resolveOwner(
        Center $center,
        ?User $existingOwner,
        ?array $ownerPayload,
        string $roleSlug
    ): User {
        $currentOwner = $this->findCenterOwner($center);
        if ($currentOwner instanceof User) {
            $this->ensureRole($currentOwner, $roleSlug);

            return $currentOwner;
        }

        if ($existingOwner instanceof User) {
            return $this->attachExistingOwner($existingOwner, $center, $roleSlug);
        }

        $payloadEmail = is_array($ownerPayload) ? ($ownerPayload['email'] ?? null) : null;
        if (is_string($payloadEmail) && $payloadEmail !== '') {
            $owner = User::where('email', $payloadEmail)->first();
            if ($owner instanceof User) {
                return $this->attachExistingOwner($owner, $center, $roleSlug);
            }
        }

        return $this->createOwner($ownerPayload, $center, $roleSlug);
    }

    private function resolveExistingOwner(Center $center, ?User $existingOwner): User
    {
        $currentOwner = $this->findCenterOwner($center);
        if ($currentOwner instanceof User) {
            return $currentOwner;
        }

        if ($existingOwner instanceof User) {
            return $existingOwner;
        }

        throw ValidationException::withMessages([
            'owner' => ['Center owner is missing.'],
        ]);
    }

    private function findCenterOwner(Center $center): ?User
    {
        /** @var User|null $owner */
        $owner = $center->users()
            ->wherePivot('type', 'owner')
            ->first();

        return $owner;
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

    private function markOnboardingStatus(Center $center, string $status): void
    {
        if ($center->onboarding_status === $status) {
            return;
        }

        $center->onboarding_status = $status;
        $center->save();
    }
}
