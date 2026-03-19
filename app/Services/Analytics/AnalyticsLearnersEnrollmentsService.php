<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\EnrollmentStatus;
use App\Enums\UserStatus;
use App\Filters\Admin\AnalyticsFilters;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Analytics\Contracts\AnalyticsLearnersEnrollmentsServiceInterface;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsLearnersEnrollmentsService implements AnalyticsLearnersEnrollmentsServiceInterface
{
    public function __construct(private readonly AnalyticsSupportService $support) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array<string, mixed>
     */
    public function handle(User $admin, AnalyticsFilters $filters): array
    {
        $cached = $this->support->remember('learners_enrollments_v2', $admin, $filters, function () use ($admin, $filters): array {
            $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);

            $studentQuery = User::query()
                ->where('is_student', true)
                ->where('created_at', '<=', $filters->to);
            $studentQuery = $studentQuery->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds));

            $totalStudents = (clone $studentQuery)->count();
            $activeStudents = (clone $studentQuery)
                ->where('status', UserStatus::Active->value)
                ->count();
            $newStudentsQuery = User::query()
                ->where('is_student', true)
                ->whereBetween('created_at', [$filters->from, $filters->to]);
            $newStudentsQuery = $newStudentsQuery->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds));

            $newStudents = $newStudentsQuery->count();

            $byCenter = [];
            if ($centerIds === null) {
                $byCenter = User::query()
                    ->where('is_student', true)
                    ->whereBetween('created_at', [$filters->from, $filters->to])
                    ->selectRaw('center_id, COUNT(*) as total')
                    ->groupBy('center_id')
                    ->orderByDesc('total')
                    ->limit(20)
                    ->get()
                    ->map(static fn (User $user): array => [
                        'center_id' => $user->center_id,
                        'students' => (int) $user->getAttribute('total'),
                    ])
                    ->values()
                    ->all();
            }

            $enrollmentCounts = Enrollment::query()
                ->selectRaw('status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $topCourseRows = Enrollment::query()
                ->selectRaw('course_id, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->groupBy('course_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $learnerTrends = $this->computeLearnerTrends($centerIds, $filters);
            $enrollmentTrends = $this->computeEnrollmentTrends($centerIds, $filters);

            return [
                'meta' => $this->support->meta($filters),
                'learners' => [
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'new_students' => $newStudents,
                    'by_center' => $byCenter,
                    'trends' => $learnerTrends,
                ],
                'enrollments' => [
                    'by_status' => $this->support->mapCounts($enrollmentCounts, [
                        'active' => EnrollmentStatus::Active->value,
                        'pending' => EnrollmentStatus::Pending->value,
                        'deactivated' => EnrollmentStatus::Deactivated->value,
                        'cancelled' => EnrollmentStatus::Cancelled->value,
                    ]),
                    'top_courses' => $this->support->mapTopCourses($topCourseRows),
                    'trends' => $enrollmentTrends,
                ],
            ];
        });

        $cached['labels'] = [
            'total_students' => __('analytics.total_students'),
            'active_students' => __('analytics.active_students'),
            'new_students' => __('analytics.new_students'),
            'active' => __('analytics.active'),
            'pending' => __('analytics.pending'),
            'deactivated' => __('analytics.deactivated'),
            'cancelled' => __('analytics.cancelled'),
            'najaah_app' => __('analytics.najaah_app'),
        ];

        return $cached;
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, array<int, array{date: string, count: int}>>
     */
    private function computeLearnerTrends(?array $centerIds, AnalyticsFilters $filters): array
    {
        $timezone = $this->support->resolveTimezone($filters->timezone);

        $registrationsByDate = $this->support->bucketDateCounts(
            User::query()
                ->where('is_student', true)
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->select(['created_at'])
                ->cursor(),
            'created_at',
            $timezone
        );

        return [
            'registrations_over_time' => $this->support->generateDateSeries($filters, $registrationsByDate),
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<string, array<int, array<string, int|string>>>
     */
    private function computeEnrollmentTrends(?array $centerIds, AnalyticsFilters $filters): array
    {
        $timezone = $this->support->resolveTimezone($filters->timezone);

        $statusMap = [
            'active' => EnrollmentStatus::Active->value,
            'pending' => EnrollmentStatus::Pending->value,
            'cancelled' => EnrollmentStatus::Cancelled->value,
            'deactivated' => EnrollmentStatus::Deactivated->value,
        ];

        $dateStatusCounts = $this->support->bucketStatusDateCounts(
            Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->select(['enrolled_at', 'status'])
                ->cursor(),
            'enrolled_at',
            'status',
            $statusMap,
            $timezone
        );

        return [
            'over_time' => $this->support->generateStatusDateSeries($filters, $dateStatusCounts, array_keys($statusMap)),
        ];
    }
}
