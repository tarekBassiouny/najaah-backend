<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use Carbon\Carbon;

/**
 * Trait for formatting dates in API resources.
 *
 * This trait provides consistent date formatting across all API resources,
 * using the timezone resolved from the request context.
 *
 * Dates are formatted in ISO 8601 format with timezone:
 * - DateTime: 2026-03-08T14:30:00+02:00
 * - Date only: 2026-03-08
 */
trait FormatsDates
{
    /**
     * Format a datetime value with timezone.
     *
     * Returns ISO 8601 format: 2026-03-08T14:30:00+02:00
     */
    protected function formatDateTime(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $timezone = $this->resolveTimezone();

        return $carbon->copy()->setTimezone($timezone)->toIso8601String();
    }

    /**
     * Format a date value (without time) with timezone.
     *
     * Returns date format: 2026-03-08
     */
    protected function formatDate(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $timezone = $this->resolveTimezone();

        return $carbon->copy()->setTimezone($timezone)->toDateString();
    }

    /**
     * Resolve the timezone from the request context.
     *
     * The timezone is set by the ResolveTimezone middleware based on:
     * 1. Explicit timezone query parameter (for analytics)
     * 2. Authenticated student's center timezone
     * 3. Route-bound center timezone (admin APIs)
     * 4. System default timezone
     */
    protected function resolveTimezone(): string
    {
        return request()->attributes->get('timezone', 'UTC');
    }
}
