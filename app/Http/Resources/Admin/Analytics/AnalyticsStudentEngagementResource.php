<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsStudentEngagementResource extends JsonResource
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
            'student' => [
                'id' => (int) data_get($resource, 'student.id', 0),
                'name' => data_get($resource, 'student.name'),
                'center_id' => data_get($resource, 'student.center_id'),
            ],
            'overview' => [
                'total_views' => (int) data_get($resource, 'overview.total_views', 0),
                'total_sessions' => (int) data_get($resource, 'overview.total_sessions', 0),
                'enrolled_courses' => (int) data_get($resource, 'overview.enrolled_courses', 0),
                'total_enrollments' => (int) data_get($resource, 'overview.total_enrollments', 0),
                'active_enrollments' => (int) data_get($resource, 'overview.active_enrollments', 0),
                'last_activity_at' => data_get($resource, 'overview.last_activity_at'),
            ],
            'center' => [
                'id' => data_get($resource, 'center.id'),
                'name' => data_get($resource, 'center.name'),
                'total_courses' => (int) data_get($resource, 'center.total_courses', 0),
                'published_courses' => (int) data_get($resource, 'center.published_courses', 0),
            ],
            'courses' => [
                'views' => data_get($resource, 'courses.views', []),
            ],
            'videos' => data_get($resource, 'videos', []),
        ];
    }
}
