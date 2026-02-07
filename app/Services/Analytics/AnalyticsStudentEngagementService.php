<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filters\Admin\AnalyticsStudentFilters;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Analytics\Contracts\AnalyticsStudentEngagementServiceInterface;
use Illuminate\Support\Collection;

final class AnalyticsStudentEngagementService implements AnalyticsStudentEngagementServiceInterface
{
    public function __construct(private readonly AnalyticsSupportService $support) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $admin, User $student, AnalyticsStudentFilters $filters): array
    {
        return $this->support->remember(
            'student_engagement:'.$student->id,
            $admin,
            $filters,
            function () use ($admin, $student, $filters): array {
                $centerIds = $this->support->resolveCenterScope($admin, $filters->centerId);
                if ($centerIds !== null && ! in_array((int) $student->center_id, $centerIds, true)) {
                    return [
                        'meta' => $this->support->meta($filters),
                        'student' => [
                            'id' => $student->id,
                        ],
                        'overview' => [
                            'total_views' => 0,
                            'total_sessions' => 0,
                            'enrolled_courses' => 0,
                            'active_enrollments' => 0,
                            'last_activity_at' => null,
                        ],
                        'center' => [
                            'id' => $student->center_id,
                            'name' => null,
                            'total_courses' => 0,
                            'published_courses' => 0,
                        ],
                        'courses' => [
                            'views' => [],
                        ],
                        'videos' => [],
                    ];
                }

                $student->loadMissing('center');

                $enrollmentQuery = Enrollment::query()
                    ->where('user_id', $student->id)
                    ->whereBetween('enrolled_at', [$filters->from, $filters->to]);

                if ($centerIds !== null) {
                    $enrollmentQuery->whereIn('center_id', $centerIds);
                }

                $totalEnrollments = (clone $enrollmentQuery)->count();
                $activeEnrollments = (clone $enrollmentQuery)
                    ->where('status', EnrollmentStatus::Active->value)
                    ->count();
                $enrolledCourses = (clone $enrollmentQuery)
                    ->distinct('course_id')
                    ->count('course_id');

                $centerCoursesQuery = Course::query()
                    ->where('center_id', (int) $student->center_id)
                    ->whereBetween('created_at', [$filters->from, $filters->to]);

                $centerTotalCourses = (clone $centerCoursesQuery)->count();
                $centerPublishedCourses = (clone $centerCoursesQuery)
                    ->where('status', CourseStatus::Published->value)
                    ->count();

                $sessionsQuery = PlaybackSession::query()
                    ->where('user_id', $student->id)
                    ->whereBetween('started_at', [$filters->from, $filters->to]);

                $totalSessions = (int) (clone $sessionsQuery)->count();
                $lastActivityAt = (clone $sessionsQuery)
                    ->selectRaw('MAX(IFNULL(last_activity_at, started_at)) as last_activity_at')
                    ->value('last_activity_at');

                $perVideoRows = (clone $sessionsQuery)
                    ->join('videos', 'videos.id', '=', 'playback_sessions.video_id')
                    ->selectRaw('playback_sessions.video_id')
                    ->selectRaw('playback_sessions.course_id')
                    ->selectRaw('MAX(playback_sessions.is_full_play) as has_full_play')
                    ->selectRaw('SUM(playback_sessions.watch_duration) as watch_duration')
                    ->selectRaw('SUM(CASE WHEN playback_sessions.is_full_play = 1 THEN 1 ELSE 0 END) as full_play_sessions')
                    ->selectRaw('COUNT(*) as total_sessions')
                    ->selectRaw('COUNT(DISTINCT DATE(playback_sessions.started_at)) as active_days')
                    ->selectRaw('AVG(CASE WHEN playback_sessions.ended_at IS NULL THEN NULL ELSE playback_sessions.watch_duration END) as avg_watch_duration')
                    ->selectRaw('MAX(IFNULL(playback_sessions.last_activity_at, playback_sessions.started_at)) as last_activity_at')
                    ->selectRaw('videos.duration_seconds as duration_seconds')
                    ->groupBy('playback_sessions.video_id', 'playback_sessions.course_id', 'videos.duration_seconds')
                    ->get();

                $videoIds = $perVideoRows->pluck('video_id')->filter()->unique()->values()->all();
                $courseIds = $perVideoRows->pluck('course_id')->filter()->unique()->values()->all();

                /** @var Collection<int, Video> $videos */
                $videos = Video::query()
                    ->whereIn('id', $videoIds)
                    ->get()
                    ->keyBy('id');

                /** @var Collection<int, Course> $courses */
                $courses = Course::query()
                    ->whereIn('id', $courseIds)
                    ->get()
                    ->keyBy('id');

                $threshold = (int) config('playback.full_play_threshold', 80);
                $requiredRatio = max(1, min(100, $threshold)) / 100;

                $totalViews = 0;
                $courseViews = [];
                $videoStats = $perVideoRows->map(function ($row) use (
                    $videos,
                    $courses,
                    $requiredRatio,
                    &$totalViews,
                    &$courseViews
                ): array {
                    $videoId = (int) ($row->video_id ?? 0);
                    $courseId = $row->course_id !== null ? (int) $row->course_id : null;
                    $durationSeconds = $row->duration_seconds !== null ? (int) $row->duration_seconds : null;
                    $watchDuration = (int) ($row->watch_duration ?? 0);
                    $hasFullPlaySession = ((int) ($row->has_full_play ?? 0)) > 0;

                    $isViewed = $hasFullPlaySession;
                    if (! $isViewed && $durationSeconds !== null && $durationSeconds > 0) {
                        $isViewed = $watchDuration >= (int) ceil($durationSeconds * $requiredRatio);
                    }

                    if ($isViewed) {
                        $totalViews++;
                        if ($courseId !== null) {
                            $courseViews[$courseId] = ($courseViews[$courseId] ?? 0) + 1;
                        }
                    }

                    $video = $videos->get($videoId);
                    $course = $courseId !== null ? $courses->get($courseId) : null;

                    return [
                        'video_id' => $videoId,
                        'video_title' => $video instanceof Video ? $video->translate('title') : null,
                        'course_id' => $courseId,
                        'course_title' => $course instanceof Course ? $course->translate('title') : null,
                        'duration_seconds' => $durationSeconds,
                        'is_viewed' => $isViewed,
                        'total_sessions' => (int) ($row->total_sessions ?? 0),
                        'full_play_sessions' => (int) ($row->full_play_sessions ?? 0),
                        'distinct_days' => (int) ($row->active_days ?? 0),
                        'average_watch_duration' => $row->avg_watch_duration !== null
                            ? (int) round((float) $row->avg_watch_duration)
                            : null,
                        'watch_duration' => $watchDuration,
                        'last_activity_at' => $row->last_activity_at !== null
                            ? (string) $row->last_activity_at
                            : null,
                    ];
                })->values()->all();

                $courseStats = collect($courseViews)->map(function ($views, $courseId) use ($courses, $perVideoRows): array {
                    $courseId = (int) $courseId;
                    $course = $courses->get($courseId);
                    $totalSessions = $perVideoRows
                        ->where('course_id', $courseId)
                        ->sum(fn ($row): int => (int) ($row->total_sessions ?? 0));

                    return [
                        'course_id' => $courseId,
                        'course_title' => $course instanceof Course ? $course->translate('title') : null,
                        'views' => (int) $views,
                        'total_sessions' => (int) $totalSessions,
                    ];
                })->values()->all();

                return [
                    'meta' => $this->support->meta($filters),
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'center_id' => $student->center_id,
                    ],
                    'overview' => [
                        'total_views' => $totalViews,
                        'total_sessions' => $totalSessions,
                        'enrolled_courses' => $enrolledCourses,
                        'total_enrollments' => $totalEnrollments,
                        'active_enrollments' => $activeEnrollments,
                        'last_activity_at' => $lastActivityAt !== null ? (string) $lastActivityAt : null,
                    ],
                    'center' => [
                        'id' => $student->center_id,
                        'name' => $student->center?->name,
                        'total_courses' => $centerTotalCourses,
                        'published_courses' => $centerPublishedCourses,
                    ],
                    'courses' => [
                        'views' => $courseStats,
                    ],
                    'videos' => $videoStats,
                ];
            }
        );
    }
}
