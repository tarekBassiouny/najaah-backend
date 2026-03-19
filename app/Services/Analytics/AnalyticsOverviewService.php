<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filters\Admin\AnalyticsFilters;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsOverviewServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnalyticsOverviewService implements AnalyticsOverviewServiceInterface
{
    public function __construct(private readonly AnalyticsSupportService $support) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array<string, mixed>
     */
    public function handle(User $admin, AnalyticsFilters $filters): array
    {
        $cached = $this->support->remember('overview_v2', $admin, $filters, function () use ($admin, $filters): array {
            $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);

            $overview = $this->computeOverviewMetrics($centerIds, $filters);
            $trends = $this->computeTrends($centerIds, $filters);
            $previousPeriod = $this->computePreviousPeriod($centerIds, $filters);

            return [
                'meta' => $this->support->meta($filters),
                'overview' => $overview,
                'trends' => $trends,
                'previous_period' => $previousPeriod,
            ];
        });

        $cached['labels'] = [
            'total_centers' => __('analytics.total_centers'),
            'active_centers' => __('analytics.active_centers'),
            'total_courses' => __('analytics.total_courses'),
            'published_courses' => __('analytics.published_courses'),
            'total_enrollments' => __('analytics.total_enrollments'),
            'active_enrollments' => __('analytics.active_enrollments'),
            'daily_active_learners' => __('analytics.daily_active_learners'),
            'published' => __('analytics.published'),
            'unpublished' => __('analytics.unpublished'),
            'branded' => __('analytics.branded'),
            'unbranded' => __('analytics.unbranded'),
            'no_centers' => __('analytics.no_centers'),
        ];

        return $cached;
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, mixed>
     */
    private function computeOverviewMetrics(?array $centerIds, AnalyticsFilters $filters): array
    {
        return $this->computeOverviewForRange($centerIds, $filters->from, $filters->to, $filters);
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, mixed>
     */
    private function computeOverviewForRange(?array $centerIds, Carbon $from, Carbon $to, AnalyticsFilters $filters): array
    {
        $totalCenters = Center::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $activeCenters = Center::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
            ->where('onboarding_status', Center::ONBOARDING_ACTIVE)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $centerTypeCounts = Center::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
            ->selectRaw('type, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $totalCourses = Course::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $publishedCourses = Course::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->where('status', CourseStatus::Published->value)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $totalEnrollments = Enrollment::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('enrolled_at', [$from, $to])
            ->count();

        $activeEnrollments = Enrollment::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->where('status', EnrollmentStatus::Active->value)
            ->whereBetween('enrolled_at', [$from, $to])
            ->count();

        $dailyActiveLearners = $this->support->countDistinctPlaybackUsers(
            new AnalyticsFilters($filters->centerId, $from, $to, $filters->timezone),
            $centerIds
        );

        return [
            'total_centers' => $totalCenters,
            'active_centers' => $activeCenters,
            'centers_by_type' => $this->support->mapCounts($centerTypeCounts, [
                'unbranded' => CenterType::Unbranded->value,
                'branded' => CenterType::Branded->value,
            ]),
            'total_courses' => $totalCourses,
            'published_courses' => $publishedCourses,
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollments,
            'daily_active_learners' => $dailyActiveLearners,
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, array<int, array{date: string, count: int}>>
     */
    private function computeTrends(?array $centerIds, AnalyticsFilters $filters): array
    {
        $timezone = $this->support->resolveTimezone($filters->timezone);

        $enrollmentsByDate = $this->support->bucketDateCounts(
            Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->select(['enrolled_at'])
                ->cursor(),
            'enrolled_at',
            $timezone
        );

        $activeLearnersByDate = $this->activeLearnersByDate($centerIds, $filters);

        $coursesByDate = $this->support->bucketDateCounts(
            Course::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->select(['created_at'])
                ->cursor(),
            'created_at',
            $timezone
        );

        return [
            'enrollments_over_time' => $this->support->generateDateSeries($filters, $enrollmentsByDate),
            'active_learners_over_time' => $this->support->generateDateSeries($filters, $activeLearnersByDate),
            'courses_created_over_time' => $this->support->generateDateSeries($filters, $coursesByDate),
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, int>
     */
    private function activeLearnersByDate(?array $centerIds, AnalyticsFilters $filters): array
    {
        $timezone = $this->support->resolveTimezone($filters->timezone);

        $query = PlaybackSession::query()
            ->whereBetween('started_at', [$filters->from, $filters->to])
            ->select(['started_at', 'playback_sessions.user_id']);

        if ($centerIds !== null) {
            $query->join('users', 'users.id', '=', 'playback_sessions.user_id')
                ->whereIn('users.center_id', $centerIds)
                ->whereNull('users.deleted_at');
        }

        return $this->support->bucketDistinctDateCounts(
            $query->cursor(),
            'started_at',
            'user_id',
            $timezone
        );
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, mixed>
     */
    private function computePreviousPeriod(?array $centerIds, AnalyticsFilters $filters): array
    {
        $prev = $this->support->previousPeriodDates($filters);

        return $this->computeOverviewForRange($centerIds, $prev['from'], $prev['to'], $filters);
    }
}
