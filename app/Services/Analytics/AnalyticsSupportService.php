<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Filters\Admin\AnalyticsFilters;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsSupportServiceInterface;
use App\Services\Centers\CenterScopeService;
use Illuminate\Support\Facades\Cache;

class AnalyticsSupportService implements AnalyticsSupportServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return array<string, mixed>
     */
    public function meta(AnalyticsFilters $filters): array
    {
        $from = $filters->from->copy()->setTimezone($filters->timezone)->toDateString();
        $to = $filters->to->copy()->setTimezone($filters->timezone)->toDateString();

        return [
            'range' => [
                'from' => $from,
                'to' => $to,
            ],
            'center_id' => $filters->centerId,
            'timezone' => $filters->timezone,
            'generated_at' => now()->toIso8601String(),
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

    private function cacheKey(string $key, User $admin, AnalyticsFilters $filters): string
    {
        $centerPart = $filters->centerId !== null ? (string) $filters->centerId : 'all';
        $from = $filters->from->toDateString();
        $to = $filters->to->toDateString();

        return implode(':', ['analytics', $key, (string) $admin->id, $centerPart, $from, $to, $filters->timezone]);
    }
}
