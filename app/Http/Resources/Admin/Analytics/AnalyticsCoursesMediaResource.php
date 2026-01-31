<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsCoursesMediaResource extends JsonResource
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
            'courses' => [
                'by_status' => [
                    'draft' => (int) data_get($resource, 'courses.by_status.draft', 0),
                    'uploading' => (int) data_get($resource, 'courses.by_status.uploading', 0),
                    'ready' => (int) data_get($resource, 'courses.by_status.ready', 0),
                    'published' => (int) data_get($resource, 'courses.by_status.published', 0),
                    'archived' => (int) data_get($resource, 'courses.by_status.archived', 0),
                ],
                'ready_to_publish' => (int) data_get($resource, 'courses.ready_to_publish', 0),
                'blocked_by_media' => (int) data_get($resource, 'courses.blocked_by_media', 0),
                'top_by_enrollments' => data_get($resource, 'courses.top_by_enrollments', []),
            ],
            'media' => [
                'videos' => [
                    'total' => (int) data_get($resource, 'media.videos.total', 0),
                    'by_upload_status' => [
                        'pending' => (int) data_get($resource, 'media.videos.by_upload_status.pending', 0),
                        'uploading' => (int) data_get($resource, 'media.videos.by_upload_status.uploading', 0),
                        'processing' => (int) data_get($resource, 'media.videos.by_upload_status.processing', 0),
                        'ready' => (int) data_get($resource, 'media.videos.by_upload_status.ready', 0),
                        'failed' => (int) data_get($resource, 'media.videos.by_upload_status.failed', 0),
                    ],
                    'by_lifecycle_status' => [
                        'pending' => (int) data_get($resource, 'media.videos.by_lifecycle_status.pending', 0),
                        'processing' => (int) data_get($resource, 'media.videos.by_lifecycle_status.processing', 0),
                        'ready' => (int) data_get($resource, 'media.videos.by_lifecycle_status.ready', 0),
                    ],
                ],
                'pdfs' => [
                    'total' => (int) data_get($resource, 'media.pdfs.total', 0),
                    'by_upload_status' => [
                        'pending' => (int) data_get($resource, 'media.pdfs.by_upload_status.pending', 0),
                        'processing' => (int) data_get($resource, 'media.pdfs.by_upload_status.processing', 0),
                        'ready' => (int) data_get($resource, 'media.pdfs.by_upload_status.ready', 0),
                    ],
                ],
            ],
        ];
    }
}
