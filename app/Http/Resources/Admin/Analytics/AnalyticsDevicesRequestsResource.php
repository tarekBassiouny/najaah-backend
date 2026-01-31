<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsDevicesRequestsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = is_array($this->resource) ? $this->resource : [];
        $meta = data_get($resource, 'meta', []);

        return [
            'meta' => [
                'range' => [
                    'from' => data_get($meta, 'range.from'),
                    'to' => data_get($meta, 'range.to'),
                ],
                'center_id' => data_get($meta, 'center_id'),
                'timezone' => data_get($meta, 'timezone'),
                'generated_at' => data_get($meta, 'generated_at'),
            ],
            'devices' => [
                'total' => (int) data_get($resource, 'devices.total', 0),
                'active' => (int) data_get($resource, 'devices.active', 0),
                'revoked' => (int) data_get($resource, 'devices.revoked', 0),
                'pending' => (int) data_get($resource, 'devices.pending', 0),
                'changes' => [
                    'pending' => (int) data_get($resource, 'devices.changes.pending', 0),
                    'approved' => (int) data_get($resource, 'devices.changes.approved', 0),
                    'rejected' => (int) data_get($resource, 'devices.changes.rejected', 0),
                    'pre_approved' => (int) data_get($resource, 'devices.changes.pre_approved', 0),
                    'by_source' => [
                        'mobile' => (int) data_get($resource, 'devices.changes.by_source.mobile', 0),
                        'otp' => (int) data_get($resource, 'devices.changes.by_source.otp', 0),
                        'admin' => (int) data_get($resource, 'devices.changes.by_source.admin', 0),
                    ],
                ],
            ],
            'requests' => [
                'extra_views' => [
                    'pending' => (int) data_get($resource, 'requests.extra_views.pending', 0),
                    'approved' => (int) data_get($resource, 'requests.extra_views.approved', 0),
                    'rejected' => (int) data_get($resource, 'requests.extra_views.rejected', 0),
                    'approval_rate' => (float) data_get($resource, 'requests.extra_views.approval_rate', 0),
                    'avg_decision_hours' => (float) data_get($resource, 'requests.extra_views.avg_decision_hours', 0),
                ],
                'enrollment' => [
                    'pending' => (int) data_get($resource, 'requests.enrollment.pending', 0),
                    'approved' => (int) data_get($resource, 'requests.enrollment.approved', 0),
                    'rejected' => (int) data_get($resource, 'requests.enrollment.rejected', 0),
                ],
            ],
        ];
    }
}
