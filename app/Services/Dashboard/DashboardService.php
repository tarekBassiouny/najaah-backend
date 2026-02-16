<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\ExtraViewRequestStatus;
use App\Filters\Admin\DashboardFilters;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Dashboard\Contracts\DashboardServiceInterface;
use App\Support\AuditActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(User $admin, DashboardFilters $filters): array
    {
        $centerIds = $this->resolveCenterScope($admin, $filters->centerId);

        $totalCourses = Course::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        $totalStudents = User::query()
            ->where('is_student', true)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        $activeEnrollments = $this->activeEnrollmentsStats($centerIds);
        $pendingApprovals = $this->pendingApprovalsStats($centerIds);

        return [
            'stats' => [
                'total_courses' => $totalCourses,
                'total_students' => $totalStudents,
                'active_enrollments' => $activeEnrollments,
                'pending_approvals' => $pendingApprovals,
            ],
            'recent_activity' => $this->recentActivity($centerIds),
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array{count:int,change_percent:float,trend:string}
     */
    private function activeEnrollmentsStats(?array $centerIds): array
    {
        $count = Enrollment::query()
            ->where('status', EnrollmentStatus::Active->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        $now = now();
        $currentFrom = $now->copy()->startOfMonth();
        $previousMonth = $now->copy()->subMonthNoOverflow();
        $previousFrom = $previousMonth->copy()->startOfMonth();
        $previousTo = $previousMonth->copy()->endOfMonth();

        $currentWindowCount = Enrollment::query()
            ->where('status', EnrollmentStatus::Active->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('enrolled_at', [$currentFrom, $now])
            ->count();

        $previousWindowCount = Enrollment::query()
            ->where('status', EnrollmentStatus::Active->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('enrolled_at', [$previousFrom, $previousTo])
            ->count();

        $changePercent = $this->calculateChangePercent($currentWindowCount, $previousWindowCount);

        return [
            'count' => $count,
            'change_percent' => $changePercent,
            'trend' => $this->trendFor($changePercent),
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array{total:int,enrollment_requests:int,device_change_requests:int,extra_view_requests:int}
     */
    private function pendingApprovalsStats(?array $centerIds): array
    {
        $enrollmentRequests = Enrollment::query()
            ->where('status', EnrollmentStatus::Pending->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        $deviceChangeRequests = DeviceChangeRequest::query()
            ->where('status', DeviceChangeRequestStatus::Pending->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        $extraViewRequests = ExtraViewRequest::query()
            ->where('status', ExtraViewRequestStatus::Pending->value)
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->count();

        return [
            'total' => $enrollmentRequests + $deviceChangeRequests + $extraViewRequests,
            'enrollment_requests' => $enrollmentRequests,
            'device_change_requests' => $deviceChangeRequests,
            'extra_view_requests' => $extraViewRequests,
        ];
    }

    /**
     * @param  array<int>|null  $centerIds
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(?array $centerIds): array
    {
        $actions = [
            AuditActions::ENROLLMENT_CREATED,
            AuditActions::EXTRA_VIEW_REQUEST_APPROVED,
            AuditActions::DEVICE_CHANGE_REQUEST_APPROVED,
        ];

        $query = AuditLog::query()
            ->with('user')
            ->whereIn('action', $actions)
            ->orderByDesc('created_at');

        if ($centerIds !== null) {
            $query->where(static function (Builder $builder) use ($centerIds): void {
                $builder->whereIn('center_id', $centerIds)
                    ->orWhere(static function (Builder $fallback) use ($centerIds): void {
                        $fallback->whereNull('center_id')
                            ->whereHas('user', static function (Builder $userQuery) use ($centerIds): void {
                                $userQuery->whereIn('center_id', $centerIds);
                            });
                    });
            });
        }

        return $query
            ->limit(3)
            ->get()
            ->map(function (AuditLog $log): array {
                return [
                    'id' => $log->id,
                    'action' => $this->mapAction($log->action),
                    'description' => $this->descriptionFor($log),
                    'actor' => [
                        'id' => $log->user_id,
                        'name' => $log->user?->name,
                    ],
                    'days_ago' => $this->daysAgo($log->created_at),
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int>|null
     */
    private function resolveCenterScope(User $admin, ?int $centerId): ?array
    {
        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            return $centerId !== null ? [$centerId] : null;
        }

        $adminCenterId = $this->centerScopeService->resolveAdminCenterId($admin);
        $this->centerScopeService->assertAdminCenterId($admin, $adminCenterId);

        if ($centerId !== null && $adminCenterId !== $centerId) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        return $adminCenterId !== null ? [$adminCenterId] : [];
    }

    private function calculateChangePercent(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function trendFor(float $changePercent): string
    {
        if ($changePercent > 0) {
            return 'up';
        }

        if ($changePercent < 0) {
            return 'down';
        }

        return 'stable';
    }

    private function mapAction(string $action): string
    {
        return match ($action) {
            AuditActions::ENROLLMENT_CREATED => 'enrollment.created',
            AuditActions::EXTRA_VIEW_REQUEST_APPROVED => 'extra_view.granted',
            AuditActions::DEVICE_CHANGE_REQUEST_APPROVED => 'device_change_request.approved',
            default => str_replace('_', '.', $action),
        };
    }

    private function descriptionFor(AuditLog $log): string
    {
        return match ($log->action) {
            AuditActions::ENROLLMENT_CREATED => 'New enrollment created.',
            AuditActions::EXTRA_VIEW_REQUEST_APPROVED => 'Extra view request approved.',
            AuditActions::DEVICE_CHANGE_REQUEST_APPROVED => 'Device change request approved.',
            default => 'Recent dashboard activity.',
        };
    }

    private function daysAgo(?Carbon $createdAt): int
    {
        if (! $createdAt instanceof Carbon) {
            return 0;
        }

        return (int) $createdAt->diffInDays(now());
    }
}
