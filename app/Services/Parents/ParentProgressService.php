<?php

declare(strict_types=1);

namespace App\Services\Parents;

use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Assessments\Contracts\AssessmentProgressServiceInterface;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use App\Services\Parents\Contracts\ParentServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class ParentProgressService implements ParentProgressServiceInterface
{
    public function __construct(
        private readonly ParentServiceInterface $parentService,
        private readonly AssessmentProgressServiceInterface $assessmentProgressService
    ) {}

    /**
     * @return Collection<int, Enrollment>
     */
    public function getStudentEnrollments(User $parent, int $studentId, ?int $centerId): Collection
    {
        $this->parentService->assertLinkedStudent($parent, $studentId, $centerId);

        return Enrollment::query()
            ->with(['course:id,title_translations,center_id,status,thumbnail', 'center:id,name'])
            ->where('user_id', $studentId)
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->active()
            ->notDeleted()
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCourseProgress(User $parent, int $studentId, int $courseId, ?int $centerId): array
    {
        $this->parentService->assertLinkedStudent($parent, $studentId, $centerId);

        /** @var User $student */
        $student = User::findOrFail($studentId);

        /** @var Course $course */
        $course = Course::findOrFail($courseId);

        return $this->assessmentProgressService->getProgressSummary($student, $course);
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    public function getQuizAttempts(User $parent, int $studentId, int $courseId, ?int $centerId): Collection
    {
        $this->parentService->assertLinkedStudent($parent, $studentId, $centerId);

        return QuizAttempt::query()
            ->with(['quiz:id,title_translations,course_id,passing_score,max_attempts'])
            ->where('user_id', $studentId)
            ->whereHas('quiz', fn ($q) => $q->where('course_id', $courseId))
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->orderByDesc('created_at')
            ->get();
    }

    public function getQuizAttemptDetail(User $parent, int $attemptId): QuizAttempt
    {
        /** @var QuizAttempt $attempt */
        $attempt = QuizAttempt::with([
            'quiz:id,title_translations,course_id,passing_score,max_attempts',
            'answers.question',
        ])->findOrFail($attemptId);

        $this->parentService->assertLinkedStudent(
            $parent,
            $attempt->user_id,
            is_numeric($attempt->center_id) ? (int) $attempt->center_id : null
        );

        return $attempt;
    }

    /**
     * @return Collection<int, AssignmentSubmission>
     */
    public function getAssignmentSubmissions(User $parent, int $studentId, int $courseId, ?int $centerId): Collection
    {
        $this->parentService->assertLinkedStudent($parent, $studentId, $centerId);

        return AssignmentSubmission::query()
            ->with(['assignment:id,title_translations,course_id,passing_score'])
            ->where('user_id', $studentId)
            ->whereHas('assignment', fn ($q) => $q->where('course_id', $courseId))
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeeklyActivity(User $parent, int $studentId, int $centerId, int $days, string $timezone): array
    {
        $this->parentService->assertLinkedStudent($parent, $studentId, $centerId);

        /** @var Center $center */
        $center = Center::findOrFail($centerId);

        $end = CarbonImmutable::now($timezone)->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $startUtc = $start->setTimezone('UTC');
        $endUtc = $end->setTimezone('UTC');

        $series = $this->initializeSeries($start, $days);

        $watchRows = PlaybackSession::query()
            ->select(['playback_sessions.started_at', 'playback_sessions.watch_duration'])
            ->join('courses', 'courses.id', '=', 'playback_sessions.course_id')
            ->where('playback_sessions.user_id', $studentId)
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
            ->where('user_id', $studentId)
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
            ->where('user_id', $studentId)
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

        return [
            'range' => [
                'days' => $days,
                'timezone' => $timezone,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'series' => $seriesItems,
            'totals' => $totals,
        ];
    }

    /**
     * @return array<string, array{date: string, watch_duration_seconds: int, quiz_attempts_count: int, assignment_submissions_count: int}>
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
