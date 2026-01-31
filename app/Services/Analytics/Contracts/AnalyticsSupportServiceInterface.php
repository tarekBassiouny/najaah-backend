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
}
