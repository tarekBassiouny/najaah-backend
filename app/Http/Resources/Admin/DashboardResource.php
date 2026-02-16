<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = is_array($this->resource) ? $this->resource : [];

        return [
            'stats' => [
                'total_courses' => (int) data_get($resource, 'stats.total_courses', 0),
                'total_students' => (int) data_get($resource, 'stats.total_students', 0),
                'active_enrollments' => [
                    'count' => (int) data_get($resource, 'stats.active_enrollments.count', 0),
                    'change_percent' => (float) data_get($resource, 'stats.active_enrollments.change_percent', 0),
                    'trend' => (string) data_get($resource, 'stats.active_enrollments.trend', 'stable'),
                ],
                'pending_approvals' => [
                    'total' => (int) data_get($resource, 'stats.pending_approvals.total', 0),
                    'enrollment_requests' => (int) data_get($resource, 'stats.pending_approvals.enrollment_requests', 0),
                    'device_change_requests' => (int) data_get($resource, 'stats.pending_approvals.device_change_requests', 0),
                    'extra_view_requests' => (int) data_get($resource, 'stats.pending_approvals.extra_view_requests', 0),
                ],
            ],
            'recent_activity' => data_get($resource, 'recent_activity', []),
        ];
    }
}
