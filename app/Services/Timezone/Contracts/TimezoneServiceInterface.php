<?php

declare(strict_types=1);

namespace App\Services\Timezone\Contracts;

use App\Models\Center;
use Carbon\Carbon;

interface TimezoneServiceInterface
{
    /**
     * Get the system-wide default timezone.
     */
    public function getSystemTimezone(): string;

    /**
     * Get the effective timezone for a center.
     */
    public function getCenterTimezone(Center $center): string;

    /**
     * Resolve the timezone based on center or fall back to system default.
     */
    public function resolveTimezone(?Center $center = null): string;

    /**
     * Format a datetime in ISO 8601 format with the given timezone.
     */
    public function formatDateTime(Carbon $date, ?string $timezone = null): string;

    /**
     * Format a date (without time) in the given timezone.
     */
    public function formatDate(Carbon $date, ?string $timezone = null): string;

    /**
     * Check if a timezone string is valid.
     */
    public function isValidTimezone(string $timezone): bool;

    /**
     * Get a list of commonly used timezones.
     *
     * @return array<string, string>
     */
    public function getCommonTimezones(): array;
}
