<?php

declare(strict_types=1);

namespace App\Services\Parents\Contracts;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ParentProgressServiceInterface
{
    /**
     * @return Collection<int, \App\Models\Enrollment>
     */
    public function getStudentEnrollments(User $parent, int $studentId, ?int $centerId): Collection;

    /**
     * @return array<string, mixed>
     */
    public function getCourseProgress(User $parent, int $studentId, int $courseId, ?int $centerId): array;

    /**
     * @return Collection<int, QuizAttempt>
     */
    public function getQuizAttempts(User $parent, int $studentId, int $courseId, ?int $centerId): Collection;

    public function getQuizAttemptDetail(User $parent, int $attemptId): QuizAttempt;

    /**
     * @return Collection<int, \App\Models\AssignmentSubmission>
     */
    public function getAssignmentSubmissions(User $parent, int $studentId, int $courseId, ?int $centerId): Collection;

    /**
     * @return array<string, mixed>
     */
    public function getWeeklyActivity(User $parent, int $studentId, int $centerId, int $days, string $timezone): array;
}
