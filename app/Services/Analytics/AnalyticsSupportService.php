<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Filters\Admin\AnalyticsFilters;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsSupportServiceInterface;
use App\Services\Centers\CenterScopeService;
use App\Services\Timezone\Contracts\TimezoneServiceInterface;
use BackedEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsSupportService implements AnalyticsSupportServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly TimezoneServiceInterface $timezoneService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function meta(AnalyticsFilters $filters): array
    {
        $timezone = $this->resolveTimezone($filters->timezone);
        $from = $filters->from->copy()->setTimezone($timezone)->toDateString();
        $to = $filters->to->copy()->setTimezone($timezone)->toDateString();

        return [
            'range' => [
                'from' => $from,
                'to' => $to,
            ],
            'center_id' => $filters->centerId,
            'timezone' => $timezone,
            'generated_at' => now()->setTimezone($timezone)->toIso8601String(),
        ];
    }

    /**
     * @param  \Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public function remember(string $key, User $admin, AnalyticsFilters $filters, \Closure $callback): array
    {
        $cacheKey = $this->cacheKey($key, $admin, $filters);
        $ttlSeconds = (int) config('analytics.cache_ttl_seconds', 600);

        return Cache::remember($cacheKey, $ttlSeconds, $callback);
    }

    /**
     * @return array<int>|null
     */
    public function resolveCenterScope(User $admin, ?int $centerId): ?array
    {
        if ($admin->center_id === null) {
            return $centerId !== null ? [$centerId] : null;
        }

        $adminCenterId = (int) $admin->center_id;
        if ($centerId !== null && $centerId !== $adminCenterId) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        return [$adminCenterId];
    }

    /**
     * @param  array<int|string, int>  $counts
     * @param  array<string, int|string>  $map
     * @return array<string, int>
     */
    public function mapCounts(array $counts, array $map): array
    {
        $result = [];
        foreach ($map as $key => $value) {
            $result[$key] = $this->countValue($counts, $value);
        }

        return $result;
    }

    /**
     * @param  array<int|string, int>  $counts
     */
    public function countValue(array $counts, int|string $value): int
    {
        $valueKey = (string) $value;
        if (array_key_exists($valueKey, $counts)) {
            return $counts[$valueKey];
        }

        if (array_key_exists($value, $counts)) {
            return $counts[$value];
        }

        return 0;
    }

    /**
     * @param  array<int>|null  $centerIds
     */
    public function countDistinctPlaybackUsers(AnalyticsFilters $filters, ?array $centerIds): int
    {
        $query = PlaybackSession::query()
            ->whereBetween('started_at', [$filters->from, $filters->to]);

        if ($centerIds !== null) {
            $query->join('users', 'users.id', '=', 'playback_sessions.user_id')
                ->whereIn('users.center_id', $centerIds)
                ->whereNull('users.deleted_at');
        }

        return (int) $query
            ->distinct('playback_sessions.user_id')
            ->count('playback_sessions.user_id');
    }

    /**
     * @param  iterable<int, array{course_id?: int|string|null, total?: int|string|null}|object{course_id?: int|string|null, total?: int|string|null}|\App\Models\Enrollment>  $rows
     * @return array<int, array<string, int|string|null>>
     */
    public function mapTopCourses(iterable $rows): array
    {
        /** @var \Illuminate\Support\Collection<int, array{course_id?: int|string|null, total?: int|string|null}|object{course_id?: int|string|null, total?: int|string|null}|\App\Models\Enrollment> $rows */
        $rows = collect($rows);
        $courseIds = $rows->map(static function ($row): int {
            if (is_array($row)) {
                return (int) ($row['course_id'] ?? 0);
            }

            return (int) ($row->course_id ?? 0);
        })->filter()->unique()->values()->all();
        if ($courseIds === []) {
            return [];
        }

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($courses): array {
            $courseId = 0;
            $total = 0;
            if (is_array($row)) {
                $courseId = (int) ($row['course_id'] ?? 0);
                $total = (int) ($row['total'] ?? 0);
            } else {
                $courseId = (int) ($row->course_id ?? 0);
                $total = (int) ($row->total ?? 0);
            }

            $course = $courses->get($courseId);
            $title = $course instanceof Course ? $course->translate('title') : null;

            return [
                'course_id' => $courseId,
                'title' => $title,
                'enrollments' => $total,
            ];
        })->values()->all();
    }

    public function resolveTimezone(?string $timezone): string
    {
        if (is_string($timezone) && $this->timezoneService->isValidTimezone($timezone)) {
            return $timezone;
        }

        return $this->timezoneService->getSystemTimezone();
    }

    /**
     * @param  iterable<mixed>  $rows
     * @return array<string, int>
     */
    public function bucketDateCounts(iterable $rows, string $timestampField, string $timezone, ?string $countField = null): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $dateKey = $this->resolveDateKey(data_get($row, $timestampField), $timezone);
            if ($dateKey === null) {
                continue;
            }

            $increment = $countField !== null ? (int) data_get($row, $countField, 0) : 1;
            $counts[$dateKey] = ($counts[$dateKey] ?? 0) + max($increment, 0);
        }

        return $counts;
    }

    /**
     * @param  iterable<mixed>  $rows
     * @return array<string, int>
     */
    public function bucketDistinctDateCounts(iterable $rows, string $timestampField, string $distinctField, string $timezone): array
    {
        $seen = [];

        foreach ($rows as $row) {
            $dateKey = $this->resolveDateKey(data_get($row, $timestampField), $timezone);
            $distinctValue = data_get($row, $distinctField);

            if ($dateKey === null || $distinctValue === null) {
                continue;
            }

            $seen[$dateKey][(string) $distinctValue] = true;
        }

        $counts = [];
        foreach ($seen as $dateKey => $values) {
            $counts[$dateKey] = count($values);
        }

        return $counts;
    }

    /**
     * @param  iterable<mixed>  $rows
     * @param  array<string, int|string>  $statusMap
     * @return array<string, array<string, int>>
     */
    public function bucketStatusDateCounts(
        iterable $rows,
        string $timestampField,
        string $statusField,
        array $statusMap,
        string $timezone,
        ?string $countField = null
    ): array {
        $reverseMap = [];
        foreach ($statusMap as $statusName => $rawValue) {
            $reverseMap[(string) $rawValue] = $statusName;
        }

        $counts = [];

        foreach ($rows as $row) {
            $dateKey = $this->resolveDateKey(data_get($row, $timestampField), $timezone);
            if ($dateKey === null) {
                continue;
            }

            $rawStatus = data_get($row, $statusField);
            if ($rawStatus instanceof BackedEnum) {
                $rawStatus = $rawStatus->value;
            }

            $statusName = $reverseMap[(string) $rawStatus] ?? null;
            if ($statusName === null) {
                continue;
            }

            $increment = $countField !== null ? (int) data_get($row, $countField, 0) : 1;
            $counts[$dateKey][$statusName] = ($counts[$dateKey][$statusName] ?? 0) + max($increment, 0);
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $dateCounts
     * @return array<int, array{date: string, count: int}>
     */
    public function generateDateSeries(AnalyticsFilters $filters, array $dateCounts): array
    {
        $series = [];
        $timezone = $this->resolveTimezone($filters->timezone);
        $current = $filters->from->copy()->setTimezone($timezone)->startOfDay();
        $end = $filters->to->copy()->setTimezone($timezone)->startOfDay();

        while ($current->lte($end)) {
            $dateKey = $current->toDateString();
            $series[] = [
                'date' => $dateKey,
                'count' => $dateCounts[$dateKey] ?? 0,
            ];
            $current->addDay();
        }

        return $series;
    }

    /**
     * @param  array<string, array<string, int>>  $dateStatusCounts
     * @param  array<int, string>  $statuses
     * @return array<int, array<string, int|string>>
     */
    public function generateStatusDateSeries(AnalyticsFilters $filters, array $dateStatusCounts, array $statuses): array
    {
        $series = [];
        $timezone = $this->resolveTimezone($filters->timezone);
        $current = $filters->from->copy()->setTimezone($timezone)->startOfDay();
        $end = $filters->to->copy()->setTimezone($timezone)->startOfDay();
        $zeroRow = array_fill_keys($statuses, 0);

        while ($current->lte($end)) {
            $dateKey = $current->toDateString();
            $row = ['date' => $dateKey];
            $dayCounts = $dateStatusCounts[$dateKey] ?? [];
            foreach ($zeroRow as $status => $zero) {
                $row[$status] = $dayCounts[$status] ?? $zero;
            }

            $series[] = $row;
            $current->addDay();
        }

        return $series;
    }

    /**
     * @return array{from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon}
     */
    public function previousPeriodDates(AnalyticsFilters $filters): array
    {
        $durationSeconds = $filters->from->diffInSeconds($filters->to);

        return [
            'from' => $filters->from->copy()->subSeconds((int) $durationSeconds)->subSecond(),
            'to' => $filters->from->copy()->subSecond(),
        ];
    }

    private function cacheKey(string $key, User $admin, AnalyticsFilters $filters): string
    {
        $centerPart = $filters->centerId !== null ? (string) $filters->centerId : 'all';
        $from = $filters->from->toDateString();
        $to = $filters->to->toDateString();

        return implode(':', ['analytics', $key, (string) $admin->id, $centerPart, $from, $to, $this->resolveTimezone($filters->timezone)]);
    }

    private function resolveDateKey(mixed $timestamp, string $timezone): ?string
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        try {
            return Carbon::parse($timestamp)->setTimezone($timezone)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
