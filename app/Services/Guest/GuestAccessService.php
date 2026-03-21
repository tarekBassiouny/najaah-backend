<?php

declare(strict_types=1);

namespace App\Services\Guest;

use App\Models\Center;
use App\Models\User;
use App\Services\Guest\Contracts\GuestAccessServiceInterface;
use App\Services\Settings\PolicySettingsService;

final class GuestAccessService implements GuestAccessServiceInterface
{
    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * Check if the given user (or null for guest) can browse content.
     * Authenticated users can always browse.
     * Guests can only browse if allowed by center settings.
     */
    public function canBrowse(?User $user, ?Center $center = null): bool
    {
        // Authenticated users can always browse
        if ($user instanceof User) {
            return true;
        }

        // Guest browsing - check center settings
        return $this->centerAllowsGuestBrowsing($center);
    }

    /**
     * Check if the center allows guest browsing.
     * For unbranded/Najaah app context (null center), guest browsing is allowed by default.
     */
    public function centerAllowsGuestBrowsing(?Center $center): bool
    {
        // No specific center context (Najaah app / unbranded browsing)
        // Allow guest browsing by default for discovery
        if ($center === null) {
            $constraints = $this->policySettingsService->systemConstraints();

            return ($constraints['force_disable_guest_browsing'] ?? false) !== true;
        }

        return $this->policySettingsService->centerAllowsGuestBrowsing($center);
    }

    /**
     * Check if the user is a guest (unauthenticated).
     */
    public function isGuest(?User $user): bool
    {
        return ! $user instanceof User;
    }

    /**
     * Get the center context for browsing.
     * Returns null for unbranded/Najaah app context.
     */
    public function getCenterContext(?int $centerId): ?Center
    {
        if ($centerId === null) {
            return null;
        }

        return Center::query()
            ->where('id', $centerId)
            ->where('status', Center::STATUS_ACTIVE->value)
            ->first();
    }
}
