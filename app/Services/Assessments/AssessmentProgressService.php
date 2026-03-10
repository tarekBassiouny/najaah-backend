<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\QuizAttemptStatus;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Assessments\Contracts\AssessmentProgressServiceInterface;
use Illuminate\Database\Eloquent\Collection;

final class AssessmentProgressService implements AssessmentProgressServiceInterface
{
    /**
     * @return array{quizzes: Collection<int, Quiz>, assignments: Collection<int, Assignment>}
     */
    public function getRequiredAssessments(Course $course): array
    {
        return [
            'quizzes' => Quiz::where('course_id', $course->id)
                ->where('is_required', true)
                ->where('is_active', true)
                ->get(),
            'assignments' => Assignment::where('course_id', $course->id)
                ->where('is_required', true)
                ->where('is_active', true)
                ->get(),
        ];
    }

    /**
     * @return array{quizzes: Collection<int, Quiz>, assignments: Collection<int, Assignment>}
     */
    public function getCompletedAssessments(User $student, Course $course): array
    {
        $passedQuizIds = Quiz::where('course_id', $course->id)
            ->whereHas('attempts', function ($query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('passed', true);
            })
            ->pluck('id');

        $passedAssignmentIds = Assignment::where('course_id', $course->id)
            ->whereHas('submissions', function ($query) use ($student): void {
                $query->where('user_id', $student->id)
                    ->where('passed', true);
            })
            ->pluck('id');

        return [
            'quizzes' => Quiz::whereIn('id', $passedQuizIds)->get(),
            'assignments' => Assignment::whereIn('id', $passedAssignmentIds)->get(),
        ];
    }

    public function hasCompletedRequiredAssessments(User $student, Course $course): bool
    {
        $required = $this->getRequiredAssessments($course);
        $completed = $this->getCompletedAssessments($student, $course);

        $requiredQuizIds = $required['quizzes']->pluck('id')->toArray();
        $completedQuizIds = $completed['quizzes']->pluck('id')->toArray();

        $requiredAssignmentIds = $required['assignments']->pluck('id')->toArray();
        $completedAssignmentIds = $completed['assignments']->pluck('id')->toArray();

        $allQuizzesPassed = empty(array_diff($requiredQuizIds, $completedQuizIds));
        $allAssignmentsPassed = empty(array_diff($requiredAssignmentIds, $completedAssignmentIds));

        return $allQuizzesPassed && $allAssignmentsPassed;
    }

    /**
     * @return array{
     *     quizzes: array{total: int, completed: int, passed: int, required: int, required_passed: int},
     *     assignments: array{total: int, completed: int, passed: int, required: int, required_passed: int},
     *     overall_completion_percentage: float,
     *     all_required_passed: bool
     * }
     */
    public function getProgressSummary(User $student, Course $course): array
    {
        $quizzes = Quiz::where('course_id', $course->id)
            ->where('is_active', true)
            ->get();

        $assignments = Assignment::where('course_id', $course->id)
            ->where('is_active', true)
            ->get();

        $quizStats = $this->calculateQuizStats($student, $quizzes);
        $assignmentStats = $this->calculateAssignmentStats($student, $assignments);

        $totalRequired = $quizStats['required'] + $assignmentStats['required'];
        $totalRequiredPassed = $quizStats['required_passed'] + $assignmentStats['required_passed'];

        $overallCompletion = $totalRequired > 0
            ? round(($totalRequiredPassed / $totalRequired) * 100, 2)
            : 100.0;

        return [
            'quizzes' => $quizStats,
            'assignments' => $assignmentStats,
            'overall_completion_percentage' => $overallCompletion,
            'all_required_passed' => $totalRequired === $totalRequiredPassed,
        ];
    }

    /**
     * @param  Collection<int, Quiz>  $quizzes
     * @return array{total: int, completed: int, passed: int, required: int, required_passed: int}
     */
    private function calculateQuizStats(User $student, Collection $quizzes): array
    {
        $total = $quizzes->count();
        $completed = 0;
        $passed = 0;
        $required = 0;
        $requiredPassed = 0;

        foreach ($quizzes as $quiz) {
            $hasCompleted = $quiz->attempts()
                ->where('user_id', $student->id)
                ->where('status', QuizAttemptStatus::Graded)
                ->exists();

            $hasPassed = $quiz->attempts()
                ->where('user_id', $student->id)
                ->where('passed', true)
                ->exists();

            if ($hasCompleted) {
                $completed++;
            }

            if ($hasPassed) {
                $passed++;
            }

            if ($quiz->is_required) {
                $required++;
                if ($hasPassed) {
                    $requiredPassed++;
                }
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'passed' => $passed,
            'required' => $required,
            'required_passed' => $requiredPassed,
        ];
    }

    /**
     * @param  Collection<int, Assignment>  $assignments
     * @return array{total: int, completed: int, passed: int, required: int, required_passed: int}
     */
    private function calculateAssignmentStats(User $student, Collection $assignments): array
    {
        $total = $assignments->count();
        $completed = 0;
        $passed = 0;
        $required = 0;
        $requiredPassed = 0;

        foreach ($assignments as $assignment) {
            $hasCompleted = $assignment->submissions()
                ->where('user_id', $student->id)
                ->where('status', SubmissionStatus::Graded)
                ->exists();

            $hasPassed = $assignment->submissions()
                ->where('user_id', $student->id)
                ->where('passed', true)
                ->exists();

            if ($hasCompleted) {
                $completed++;
            }

            if ($hasPassed) {
                $passed++;
            }

            if ($assignment->is_required) {
                $required++;
                if ($hasPassed) {
                    $requiredPassed++;
                }
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'passed' => $passed,
            'required' => $required,
            'required_passed' => $requiredPassed,
        ];
    }

    /**
     * @return array<int, array{quiz_id: int, title: string, is_required: bool, attempts: int, best_score: ?float, passed: bool}>
     */
    public function getQuizProgress(User $student, Course $course): array
    {
        $quizzes = Quiz::where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $progress = [];

        foreach ($quizzes as $quiz) {
            $attempts = $quiz->attempts()
                ->where('user_id', $student->id)
                ->where('status', QuizAttemptStatus::Graded)
                ->get();

            $progress[] = [
                'quiz_id' => $quiz->id,
                'title' => $quiz->translate('title'),
                'is_required' => $quiz->is_required,
                'attempts' => $attempts->count(),
                'best_score' => $attempts->isNotEmpty() ? (float) $attempts->max('score') : null,
                'passed' => $attempts->contains('passed', true),
            ];
        }

        return $progress;
    }

    /**
     * @return array<int, array{assignment_id: int, title: string, is_required: bool, status: string, score: ?float, passed: bool}>
     */
    public function getAssignmentProgress(User $student, Course $course): array
    {
        $assignments = Assignment::where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $progress = [];

        foreach ($assignments as $assignment) {
            $submission = $assignment->submissions()
                ->where('user_id', $student->id)
                ->latest()
                ->first();

            $progress[] = [
                'assignment_id' => $assignment->id,
                'title' => $assignment->translate('title'),
                'is_required' => $assignment->is_required,
                'status' => $submission?->status->label() ?? 'Not Started',
                'score' => $submission?->score_after_penalty !== null ? (float) $submission->score_after_penalty : null,
                'passed' => $submission?->passed ?? false,
            ];
        }

        return $progress;
    }
}
