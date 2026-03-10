<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListWeeklyActivityRequest;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\PlaybackSession;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class WeeklyActivityController extends Controller
{
    public function index(ListWeeklyActivityRequest $request, Center $center): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access weekly activity.',
                ],
            ], 403);
        }

        if (! $student->belongsToCenter((int) $center->id)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Student does not belong to this center.',
                ],
            ], 403);
        }

        $days = (int) ($request->validated('days') ?? 7);
        $timezone = (string) $request->attributes->get('timezone', (string) config('app.timezone', 'UTC'));
        $end = CarbonImmutable::now($timezone)->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $startUtc = $start->setTimezone('UTC');
        $endUtc = $end->setTimezone('UTC');

        $series = $this->initializeSeries($start, $days);
        $watchRows = PlaybackSession::query()
            ->select(['playback_sessions.started_at', 'playback_sessions.watch_duration'])
            ->join('courses', 'courses.id', '=', 'playback_sessions.course_id')
            ->where('playback_sessions.user_id', $student->id)
            ->where('courses.center_id', $center->id)
            ->whereNull('playback_sessions.deleted_at')
            ->whereNotNull('playback_sessions.started_at')
            ->whereBetween('playback_sessions.started_at', [$startUtc, $endUtc])
            ->get();

        foreach ($watchRows as $row) {
            if (! $row->started_at instanceof \Carbon\CarbonInterface) {
                continue;
            }

            $dateKey = $row->started_at->copy()->setTimezone($timezone)->toDateString();
            if (! array_key_exists($dateKey, $series)) {
                continue;
            }

            $series[$dateKey]['watch_duration_seconds'] += (int) ($row->watch_duration ?? 0);
        }

        $attemptRows = QuizAttempt::query()
            ->select(['started_at', 'created_at'])
            ->where('user_id', $student->id)
            ->where('center_id', $center->id)
            ->where(function ($query) use ($startUtc, $endUtc): void {
                $query->whereBetween('started_at', [$startUtc, $endUtc])
                    ->orWhere(function ($query) use ($startUtc, $endUtc): void {
                        $query->whereNull('started_at')
                            ->whereBetween('created_at', [$startUtc, $endUtc]);
                    });
            })
            ->get();

        foreach ($attemptRows as $row) {
            $occurredAt = $row->started_at ?? $row->created_at;
            if (! $occurredAt instanceof \Carbon\CarbonInterface) {
                continue;
            }

            $dateKey = $occurredAt->copy()->setTimezone($timezone)->toDateString();
            if (! array_key_exists($dateKey, $series)) {
                continue;
            }

            $series[$dateKey]['quiz_attempts_count']++;
        }

        $submissionRows = AssignmentSubmission::query()
            ->select(['submitted_at'])
            ->where('user_id', $student->id)
            ->where('center_id', $center->id)
            ->whereNull('deleted_at')
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$startUtc, $endUtc])
            ->get();

        foreach ($submissionRows as $row) {
            if (! $row->submitted_at instanceof \Carbon\CarbonInterface) {
                continue;
            }

            $dateKey = $row->submitted_at->copy()->setTimezone($timezone)->toDateString();
            if (! array_key_exists($dateKey, $series)) {
                continue;
            }

            $series[$dateKey]['assignment_submissions_count']++;
        }

        $seriesItems = array_values($series);
        $totals = [
            'watch_duration_seconds' => array_sum(array_column($seriesItems, 'watch_duration_seconds')),
            'quiz_attempts_count' => array_sum(array_column($seriesItems, 'quiz_attempts_count')),
            'assignment_submissions_count' => array_sum(array_column($seriesItems, 'assignment_submissions_count')),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'range' => [
                    'days' => $days,
                    'timezone' => $timezone,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'series' => $seriesItems,
                'totals' => $totals,
            ],
        ]);
    }

    /**
     * @return array<string, array{
     *     date:string,
     *     watch_duration_seconds:int,
     *     quiz_attempts_count:int,
     *     assignment_submissions_count:int
     * }>
     */
    private function initializeSeries(CarbonImmutable $start, int $days): array
    {
        $series = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->addDays($offset)->toDateString();
            $series[$date] = [
                'date' => $date,
                'watch_duration_seconds' => 0,
                'quiz_attempts_count' => 0,
                'assignment_submissions_count' => 0,
            ];
        }

        return $series;
    }
}
