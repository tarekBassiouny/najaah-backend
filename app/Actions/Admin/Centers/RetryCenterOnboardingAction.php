<?php

declare(strict_types=1);

namespace App\Actions\Admin\Centers;

use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RetryCenterOnboardingAction
{
    /**
     * @return array{center: Center, owner: User, email_sent: bool}
     */
    public function execute(Center $center): array
    {
        try {
            if ($center->onboarding_status === Center::ONBOARDING_ACTIVE) {
                $owner = $this->resolveExistingOwner($center);
                $emailSent = $this->dispatchInvitation($center, $owner);

                return [
                    'center' => $center->fresh(['setting']) ?? $center,
                    'owner' => $owner,
                    'email_sent' => $emailSent,
                ];
            }

            $this->markOnboardingStatus($center, Center::ONBOARDING_IN_PROGRESS);

            $owner = $this->resolveExistingOwner($center);

            $this->markOnboardingStatus($center, Center::ONBOARDING_ACTIVE);
            $emailSent = $this->dispatchInvitation($center, $owner);

            return [
                'center' => $center->fresh(['setting']) ?? $center,
                'owner' => $owner,
                'email_sent' => $emailSent,
            ];
        } catch (\Throwable $throwable) {
            $freshCenter = $center->fresh() ?? $center;
            $this->markOnboardingStatus($freshCenter, Center::ONBOARDING_FAILED);
            throw $throwable;
        }
    }

    private function resolveExistingOwner(Center $center): User
    {
        $currentOwner = $this->findCenterOwner($center);
        if ($currentOwner instanceof User) {
            return $currentOwner;
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
