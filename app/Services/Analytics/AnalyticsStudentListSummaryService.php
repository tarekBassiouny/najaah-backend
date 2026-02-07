<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;

final class AnalyticsStudentListSummaryService
{
    /**
     * @param  iterable<int, User>  $students
     */
    public function hydrate(iterable $students): void
    {
        $collection = collect($students);
        if ($collection->isEmpty()) {
            return;
        }

        $studentIds = $collection->pluck('id')->filter()->unique()->values()->all();
        if ($studentIds === []) {
            return;
        }

        $enrollmentRows = Enrollment::query()
            ->selectRaw('user_id')
            ->selectRaw('COUNT(*) as total_enrollments')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_enrollments', [
                EnrollmentStatus::Active->value,
            ])
            ->whereIn('user_id', $studentIds)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $sessionRows = PlaybackSession::query()
            ->selectRaw('user_id')
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('SUM(CASE WHEN is_full_play = 1 THEN 1 ELSE 0 END) as full_play_sessions')
            ->selectRaw('MAX(IFNULL(last_activity_at, started_at)) as last_activity_at')
            ->whereIn('user_id', $studentIds)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $viewRows = PlaybackSession::query()
            ->join('videos', 'videos.id', '=', 'playback_sessions.video_id')
            ->selectRaw('playback_sessions.user_id')
            ->selectRaw('playback_sessions.video_id')
            ->selectRaw('MAX(playback_sessions.is_full_play) as has_full_play')
            ->selectRaw('SUM(playback_sessions.watch_duration) as watch_duration')
            ->selectRaw('videos.duration_seconds as duration_seconds')
            ->whereIn('playback_sessions.user_id', $studentIds)
            ->groupBy('playback_sessions.user_id', 'playback_sessions.video_id', 'videos.duration_seconds')
            ->get();

        $threshold = (int) config('playback.full_play_threshold', 80);
        $requiredRatio = max(1, min(100, $threshold)) / 100;

        $viewCounts = [];
        foreach ($viewRows as $row) {
            $userId = (int) ($row->user_id ?? 0);
            if ($userId === 0) {
                continue;
            }

            $durationSeconds = $row->duration_seconds !== null ? (int) $row->duration_seconds : null;
            $watchDuration = (int) ($row->watch_duration ?? 0);
            $hasFullPlaySession = ((int) ($row->has_full_play ?? 0)) > 0;

            $isViewed = $hasFullPlaySession;
            if (! $isViewed && $durationSeconds !== null && $durationSeconds > 0) {
                $isViewed = $watchDuration >= (int) ceil($durationSeconds * $requiredRatio);
            }

            if ($isViewed) {
                $viewCounts[$userId] = ($viewCounts[$userId] ?? 0) + 1;
            }
        }

        $collection->each(function (User $student) use (
            $enrollmentRows,
            $sessionRows,
            $viewCounts
        ): void {
            $enrollmentRow = $enrollmentRows->get($student->id);
            $sessionRow = $sessionRows->get($student->id);

            $student->setAttribute('analytics_summary', [
                'total_enrollments' => (int) ($enrollmentRow->total_enrollments ?? 0),
                'active_enrollments' => (int) ($enrollmentRow->active_enrollments ?? 0),
                'total_sessions' => (int) ($sessionRow->total_sessions ?? 0),
                'full_play_sessions' => (int) ($sessionRow->full_play_sessions ?? 0),
                'viewed_videos' => $viewCounts[$student->id] ?? 0,
                'last_activity_at' => $sessionRow?->last_activity_at !== null
                    ? (string) $sessionRow->last_activity_at
                    : null,
            ]);
        });
    }
}
