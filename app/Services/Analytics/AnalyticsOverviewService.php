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
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsOverviewServiceInterface;
use Illuminate\Database\Eloquent\Builder;

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
        return $this->support->remember('overview', $admin, $filters, function () use ($admin, $filters): array {
            $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);

            $totalCenters = Center::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();
            $activeCenters = Center::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
                ->where('onboarding_status', Center::ONBOARDING_ACTIVE)
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();
            $centerTypeCounts = Center::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $centerIds))
                ->selectRaw('type, COUNT(*) as total')
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            $totalCourses = Course::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();

            $publishedCourses = Course::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->where('status', CourseStatus::Published->value)
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();

            $totalEnrollments = Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->count();

            $activeEnrollments = Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->where('status', EnrollmentStatus::Active->value)
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->count();

            $dailyActiveLearners = $this->support->countDistinctPlaybackUsers($filters, $centerIds);

            return [
                'meta' => $this->support->meta($filters),
                'overview' => [
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
                ],
            ];
        });
    }
}
