<?php

declare(strict_types=1);

namespace App\Services\Guest\Contracts;

use App\Models\Center;
use App\Models\User;

interface GuestAccessServiceInterface
{
    /**
     * Check if the given user (or null for guest) can browse content.
     * Authenticated users can always browse.
     * Guests can only browse if allowed by center settings.
     */
    public function canBrowse(?User $user, ?Center $center = null): bool;

    /**
     * Check if the center allows guest browsing.
     */
    public function centerAllowsGuestBrowsing(?Center $center): bool;

    /**
     * Check if the user is a guest (unauthenticated).
     */
    public function isGuest(?User $user): bool;

    /**
     * Get the center context for browsing.
     * Returns null for unbranded/Najaah app context.
     */
    public function getCenterContext(?int $centerId): ?Center;
}
