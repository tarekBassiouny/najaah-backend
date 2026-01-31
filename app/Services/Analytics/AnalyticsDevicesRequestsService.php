<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\DeviceChangeRequestSource;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\ExtraViewRequestStatus;
use App\Enums\UserDeviceStatus;
use App\Filters\Admin\AnalyticsFilters;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Analytics\Contracts\AnalyticsDevicesRequestsServiceInterface;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsDevicesRequestsService implements AnalyticsDevicesRequestsServiceInterface
{
    public function __construct(private readonly AnalyticsSupportService $support) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array<string, mixed>
     */
    public function handle(User $admin, AnalyticsFilters $filters): array
    {
        return $this->support->remember('devices_requests', $admin, $filters, function () use ($admin, $filters): array {
            $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);

            $deviceQuery = UserDevice::query()
                ->whereBetween('user_devices.created_at', [$filters->from, $filters->to]);
            if ($centerIds !== null) {
                $deviceQuery->join('users', 'users.id', '=', 'user_devices.user_id')
                    ->whereIn('users.center_id', $centerIds)
                    ->whereNull('users.deleted_at');
            }

            $deviceCounts = $deviceQuery
                ->selectRaw('user_devices.status, COUNT(*) as total')
                ->groupBy('user_devices.status')
                ->pluck('total', 'user_devices.status')
                ->toArray();

            $deviceChangeStatusCounts = DeviceChangeRequest::query()
                ->selectRaw('status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $deviceChangeSourceCounts = DeviceChangeRequest::query()
                ->selectRaw('request_source, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('request_source')
                ->pluck('total', 'request_source')
                ->toArray();

            $extraViewCounts = ExtraViewRequest::query()
                ->selectRaw('status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $approvedExtraViews = ExtraViewRequest::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->where('status', ExtraViewRequestStatus::Approved->value)
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();

            $decidedExtraViews = ExtraViewRequest::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereIn('status', [
                    ExtraViewRequestStatus::Approved->value,
                    ExtraViewRequestStatus::Rejected->value,
                ])
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->count();

            $approvalRate = $decidedExtraViews > 0
                ? round($approvedExtraViews / $decidedExtraViews, 2)
                : 0.0;

            $avgDecisionHours = $this->calculateAverageDecisionHours($centerIds, $filters);

            $pendingEnrollments = Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->where('status', EnrollmentStatus::Pending->value)
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->count();

            $approvedEnrollments = Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->where('status', EnrollmentStatus::Active->value)
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->count();

            $rejectedEnrollments = Enrollment::query()
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereIn('status', [EnrollmentStatus::Cancelled->value, EnrollmentStatus::Deactivated->value])
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->count();

            return [
                'meta' => $this->support->meta($filters),
                'devices' => [
                    'total' => array_sum(array_map('intval', $deviceCounts)),
                    'active' => $this->support->countValue($deviceCounts, UserDeviceStatus::Active->value),
                    'revoked' => $this->support->countValue($deviceCounts, UserDeviceStatus::Revoked->value),
                    'pending' => $this->support->countValue($deviceCounts, UserDeviceStatus::Pending->value),
                    'changes' => [
                        'pending' => $this->support->countValue($deviceChangeStatusCounts, DeviceChangeRequestStatus::Pending->value),
                        'approved' => $this->support->countValue($deviceChangeStatusCounts, DeviceChangeRequestStatus::Approved->value),
                        'rejected' => $this->support->countValue($deviceChangeStatusCounts, DeviceChangeRequestStatus::Rejected->value),
                        'pre_approved' => $this->support->countValue($deviceChangeStatusCounts, DeviceChangeRequestStatus::PreApproved->value),
                        'by_source' => $this->support->mapCounts($deviceChangeSourceCounts, [
                            'mobile' => DeviceChangeRequestSource::Mobile->value,
                            'otp' => DeviceChangeRequestSource::Otp->value,
                            'admin' => DeviceChangeRequestSource::Admin->value,
                        ]),
                    ],
                ],
                'requests' => [
                    'extra_views' => [
                        'pending' => $this->support->countValue($extraViewCounts, ExtraViewRequestStatus::Pending->value),
                        'approved' => $this->support->countValue($extraViewCounts, ExtraViewRequestStatus::Approved->value),
                        'rejected' => $this->support->countValue($extraViewCounts, ExtraViewRequestStatus::Rejected->value),
                        'approval_rate' => $approvalRate,
                        'avg_decision_hours' => $avgDecisionHours,
                    ],
                    'enrollment' => [
                        'pending' => $pendingEnrollments,
                        'approved' => $approvedEnrollments,
                        'rejected' => $rejectedEnrollments,
                    ],
                ],
            ];
        });
    }

    /**
     * Calculate average decision hours using PHP for database compatibility.
     *
     * @param  array<int>|null  $centerIds
     */
    private function calculateAverageDecisionHours(?array $centerIds, AnalyticsFilters $filters): float
    {
        $requests = ExtraViewRequest::query()
            ->select(['created_at', 'decided_at'])
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereNotNull('decided_at')
            ->whereBetween('created_at', [$filters->from, $filters->to])
            ->get();

        if ($requests->isEmpty()) {
            return 0.0;
        }

        $totalHours = $requests->sum(function (ExtraViewRequest $request): float {
            return $request->created_at->diffInHours($request->decided_at);
        });

        return round($totalHours / $requests->count(), 2);
    }
}
