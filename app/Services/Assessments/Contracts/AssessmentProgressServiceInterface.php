<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentProgressServiceInterface
{
    /**
     * Get all required assessments (quizzes and assignments) for a course.
     *
     * @return array{quizzes: Collection, assignments: Collection}
     */
    public function getRequiredAssessments(Course $course): array;

    /**
     * Get completed assessments for a student in a course.
     *
     * @return array{quizzes: Collection, assignments: Collection}
     */
    public function getCompletedAssessments(User $student, Course $course): array;

    /**
     * Check if a student has completed all required assessments for a course.
     */
    public function hasCompletedRequiredAssessments(User $student, Course $course): bool;

    /**
     * Get a comprehensive progress summary for a student in a course.
     *
     * @return array{
     *     quizzes: array{total: int, completed: int, passed: int, required: int, required_passed: int},
     *     assignments: array{total: int, completed: int, passed: int, required: int, required_passed: int},
     *     overall_completion_percentage: float,
     *     all_required_passed: bool
     * }
     */
    public function getProgressSummary(User $student, Course $course): array;

    /**
     * Get detailed quiz progress for a student.
     *
     * @return array<int, array{quiz_id: int, title: string, is_required: bool, attempts: int, best_score: ?float, passed: bool}>
     */
    public function getQuizProgress(User $student, Course $course): array;

    /**
     * Get detailed assignment progress for a student.
     *
     * @return array<int, array{assignment_id: int, title: string, is_required: bool, status: string, score: ?float, passed: bool}>
     */
    public function getAssignmentProgress(User $student, Course $course): array;
}
