<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\CourseStatus;
use App\Enums\PdfUploadStatus;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Filters\Admin\AnalyticsFilters;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Analytics\Contracts\AnalyticsCoursesMediaServiceInterface;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsCoursesMediaService implements AnalyticsCoursesMediaServiceInterface
{
    public function __construct(private readonly AnalyticsSupportService $support) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array<string, mixed>
     */
    public function handle(User $admin, AnalyticsFilters $filters): array
    {
        return $this->support->remember('courses_media', $admin, $filters, function () use ($admin, $filters): array {
            $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);

            $courseStatusCounts = Course::query()
                ->selectRaw('status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $byStatus = $this->support->mapCounts($courseStatusCounts, [
                'draft' => CourseStatus::Draft->value,
                'uploading' => CourseStatus::Uploading->value,
                'ready' => CourseStatus::Ready->value,
                'published' => CourseStatus::Published->value,
                'archived' => CourseStatus::Archived->value,
            ]);

            $readyToPublish = $this->countReadyCourses($centerIds, $filters);
            $blockedByMedia = $this->countBlockedCourses($centerIds, $filters);

            $topCourseRows = Enrollment::query()
                ->selectRaw('course_id, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('enrolled_at', [$filters->from, $filters->to])
                ->groupBy('course_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $topCourses = $this->support->mapTopCourses($topCourseRows);

            $videoUploadCounts = Video::query()
                ->selectRaw('encoding_status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('encoding_status')
                ->pluck('total', 'encoding_status')
                ->toArray();

            $videoLifecycleCounts = Video::query()
                ->selectRaw('lifecycle_status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('lifecycle_status')
                ->pluck('total', 'lifecycle_status')
                ->toArray();

            $pdfUploadCounts = PdfUploadSession::query()
                ->selectRaw('upload_status, COUNT(*) as total')
                ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                ->whereBetween('created_at', [$filters->from, $filters->to])
                ->groupBy('upload_status')
                ->pluck('total', 'upload_status')
                ->toArray();

            return [
                'meta' => $this->support->meta($filters),
                'courses' => [
                    'by_status' => $byStatus,
                    'ready_to_publish' => $readyToPublish,
                    'blocked_by_media' => $blockedByMedia,
                    'top_by_enrollments' => $topCourses,
                ],
                'media' => [
                    'videos' => [
                        'total' => Video::query()
                            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                            ->whereBetween('created_at', [$filters->from, $filters->to])
                            ->count(),
                        'by_upload_status' => $this->support->mapCounts($videoUploadCounts, [
                            'pending' => VideoUploadStatus::Pending->value,
                            'uploading' => VideoUploadStatus::Uploading->value,
                            'processing' => VideoUploadStatus::Processing->value,
                            'ready' => VideoUploadStatus::Ready->value,
                            'failed' => VideoUploadStatus::Failed->value,
                        ]),
                        'by_lifecycle_status' => $this->support->mapCounts($videoLifecycleCounts, [
                            'pending' => VideoLifecycleStatus::Pending->value,
                            'processing' => VideoLifecycleStatus::Processing->value,
                            'ready' => VideoLifecycleStatus::Ready->value,
                        ]),
                    ],
                    'pdfs' => [
                        'total' => Pdf::query()
                            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
                            ->whereBetween('created_at', [$filters->from, $filters->to])
                            ->count(),
                        'by_upload_status' => $this->support->mapCounts($pdfUploadCounts, [
                            'pending' => PdfUploadStatus::Pending->value,
                            'processing' => PdfUploadStatus::Uploading->value,
                            'ready' => PdfUploadStatus::Ready->value,
                        ]),
                    ],
                ],
            ];
        });
    }

    /**
     * @param  array<int>|null  $centerIds
     */
    private function countReadyCourses(?array $centerIds, AnalyticsFilters $filters): int
    {
        $query = Course::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('created_at', [$filters->from, $filters->to])
            ->where('status', CourseStatus::Draft->value)
            ->whereDoesntHave('videos', function (Builder $query): void {
                $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                    ->orWhere('lifecycle_status', '!=', VideoLifecycleStatus::Ready->value)
                    ->orWhereHas('uploadSession', function (Builder $query): void {
                        $query->where('upload_status', '!=', VideoUploadStatus::Ready->value);
                    });
            })
            ->whereDoesntHave('pdfs', function (Builder $query): void {
                $query->whereHas('uploadSession', function (Builder $query): void {
                    $query->where('upload_status', '!=', PdfUploadStatus::Ready->value);
                });
            });

        return $query->count();
    }

    /**
     * @param  array<int>|null  $centerIds
     */
    private function countBlockedCourses(?array $centerIds, AnalyticsFilters $filters): int
    {
        $query = Course::query()
            ->when($centerIds !== null, fn (Builder $query): Builder => $query->whereIn('center_id', $centerIds))
            ->whereBetween('created_at', [$filters->from, $filters->to])
            ->where('status', CourseStatus::Draft->value)
            ->where(function (Builder $query): void {
                $query->whereHas('videos', function (Builder $query): void {
                    $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                        ->orWhere('lifecycle_status', '!=', VideoLifecycleStatus::Ready->value)
                        ->orWhereHas('uploadSession', function (Builder $query): void {
                            $query->where('upload_status', '!=', VideoUploadStatus::Ready->value);
                        });
                })
                    ->orWhereHas('pdfs', function (Builder $query): void {
                        $query->whereHas('uploadSession', function (Builder $query): void {
                            $query->where('upload_status', '!=', PdfUploadStatus::Ready->value);
                        });
                    });
            });

        return $query->count();
    }
}
