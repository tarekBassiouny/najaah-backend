<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsOverviewResource extends JsonResource
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
            'overview' => [
                'total_centers' => (int) data_get($resource, 'overview.total_centers', 0),
                'active_centers' => (int) data_get($resource, 'overview.active_centers', 0),
                'centers_by_type' => [
                    'unbranded' => (int) data_get($resource, 'overview.centers_by_type.unbranded', 0),
                    'branded' => (int) data_get($resource, 'overview.centers_by_type.branded', 0),
                ],
                'total_courses' => (int) data_get($resource, 'overview.total_courses', 0),
                'published_courses' => (int) data_get($resource, 'overview.published_courses', 0),
                'total_enrollments' => (int) data_get($resource, 'overview.total_enrollments', 0),
                'active_enrollments' => (int) data_get($resource, 'overview.active_enrollments', 0),
                'daily_active_learners' => (int) data_get($resource, 'overview.daily_active_learners', 0),
            ],
        ];
    }
}
