<?php

declare(strict_types=1);

namespace App\Services\Analytics\Contracts;

use App\Filters\Admin\AnalyticsFilters;
use App\Models\User;
use Closure;

interface AnalyticsSupportServiceInterface
{
    /**
     * Generate metadata for analytics response.
     *
     * @return array<string, mixed>
     */
    public function meta(AnalyticsFilters $filters): array;

    /**
     * Cache and return analytics data.
     *
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public function remember(string $key, User $admin, AnalyticsFilters $filters, Closure $callback): array;

    /**
     * Resolve center scope based on admin permissions.
     *
     * @return array<int>|null
     */
    public function resolveCenterScope(User $admin, ?int $centerId): ?array;

    /**
     * Map count values to named keys.
     *
     * @param  array<int|string, int>  $counts
     * @param  array<string, int|string>  $map
     * @return array<string, int>
     */
    public function mapCounts(array $counts, array $map): array;

    /**
     * Get count value from array by key.
     *
     * @param  array<int|string, int>  $counts
     */
    public function countValue(array $counts, int|string $value): int;

    /**
     * Count distinct users with playback sessions in the given period.
     *
     * @param  array<int>|null  $centerIds
     */
    public function countDistinctPlaybackUsers(AnalyticsFilters $filters, ?array $centerIds): int;

    /**
     * Map enrollment rows to top courses format.
     *
     * @param  iterable<int, array{course_id?: int|string|null, total?: int|string|null}|object>  $rows
     * @return array<int, array<string, int|string|null>>
     */
    public function mapTopCourses(iterable $rows): array;

    /**
     * Generate a date series with zero-filled gaps for every day in the range.
     *
     * @param  array<string, int>  $dateCounts  Date (YYYY-MM-DD in request timezone) => count
     * @return array<int, array{date: string, count: int}>
     */
    public function generateDateSeries(AnalyticsFilters $filters, array $dateCounts): array;

    /**
     * Generate a multi-status date series with zero-filled gaps.
     *
     * @param  array<string, array<string, int>>  $dateStatusCounts  Date => [status => count]
     * @param  array<int, string>  $statuses  List of status keys to include
     * @return array<int, array<string, int|string>>
     */
    public function generateStatusDateSeries(AnalyticsFilters $filters, array $dateStatusCounts, array $statuses): array;

    /**
     * Compute previous period date range of equal length preceding the current range.
     *
     * @return array{from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon}
     */
    public function previousPeriodDates(AnalyticsFilters $filters): array;
}
