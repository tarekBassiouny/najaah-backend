<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsLearnersEnrollmentsResource extends JsonResource
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
            'learners' => [
                'total_students' => (int) data_get($resource, 'learners.total_students', 0),
                'active_students' => (int) data_get($resource, 'learners.active_students', 0),
                'new_students' => (int) data_get($resource, 'learners.new_students', 0),
                'by_center' => data_get($resource, 'learners.by_center', []),
            ],
            'enrollments' => [
                'by_status' => [
                    'active' => (int) data_get($resource, 'enrollments.by_status.active', 0),
                    'pending' => (int) data_get($resource, 'enrollments.by_status.pending', 0),
                    'deactivated' => (int) data_get($resource, 'enrollments.by_status.deactivated', 0),
                    'cancelled' => (int) data_get($resource, 'enrollments.by_status.cancelled', 0),
                ],
                'top_courses' => data_get($resource, 'enrollments.top_courses', []),
            ],
        ];
    }
}
