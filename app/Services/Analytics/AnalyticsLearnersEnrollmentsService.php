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
        return $this->support->remember('learners_enrollments', $admin, $filters, function () use ($admin, $filters): array {
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

            return [
                'meta' => $this->support->meta($filters),
                'learners' => [
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'new_students' => $newStudents,
                    'by_center' => $byCenter,
                ],
                'enrollments' => [
                    'by_status' => $this->support->mapCounts($enrollmentCounts, [
                        'active' => EnrollmentStatus::Active->value,
                        'pending' => EnrollmentStatus::Pending->value,
                        'deactivated' => EnrollmentStatus::Deactivated->value,
                        'cancelled' => EnrollmentStatus::Cancelled->value,
                    ]),
                    'top_courses' => $this->support->mapTopCourses($topCourseRows),
                ],
            ];
        });
    }
}
