<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Contracts\AgentInterface;
use App\Enums\AgentType;
use App\Enums\EnrollmentStatus;
use App\Models\AgentExecution;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Support\AuditActions;

/**
 * Enrollment Management Agent
 *
 * Automates bulk enrollment workflows:
 * 1. Parse enrollment request (student IDs or CSV)
 * 2. Validate student identities
 * 3. Validate course access
 * 4. Check enrollment limits
 * 5. Create enrollments in batch
 * 6. Send welcome notifications
 * 7. Create audit logs
 */
final class EnrollmentManagementAgent implements AgentInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService,
        private readonly EnrollmentServiceInterface $enrollmentService
    ) {}

    public function getType(): AgentType
    {
        return AgentType::Enrollment;
    }

    public function getName(): string
    {
        return 'Bulk Enrollment';
    }

    public function getDescription(): string
    {
        return 'Enrolls multiple students in a course at once, with validation and notifications.';
    }

    /**
     * @return array<int, string>
     */
    public function getSteps(): array
    {
        return [
            'parse_request',
            'validate_students',
            'validate_course',
            'check_limits',
            'create_enrollments',
            'send_notifications',
            'create_audit_logs',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string[]>
     */
    public function validateContext(array $context): array
    {
        $errors = [];

        if (! isset($context['course_id'])) {
            $errors['course_id'] = ['Course ID is required.'];
        } elseif (! is_numeric($context['course_id'])) {
            $errors['course_id'] = ['Course ID must be numeric.'];
        }

        if (! isset($context['student_ids']) || ! is_array($context['student_ids'])) {
            $errors['student_ids'] = ['Student IDs array is required.'];
        } elseif (empty($context['student_ids'])) {
            $errors['student_ids'] = ['At least one student ID is required.'];
        }

        return $errors;
    }

    public function canExecute(User $actor): bool
    {
        return $actor->can('admin.enrollments.create');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(AgentExecution $execution, User $actor, array $context): array
    {
        $execution->markAsRunning();

        $results = [
            'success' => true,
            'steps' => [],
            'enrollments_created' => 0,
            'errors' => [],
        ];

        try {
            // Step 1: Parse request
            $parsed = $this->parseRequest($context);
            $results['steps']['parse_request'] = $parsed;
            $execution->addCompletedStep('parse_request');

            // Step 2: Validate students
            $studentIds = $parsed['student_ids'];
            $validStudents = $this->validateStudents($studentIds, $execution->center_id);
            $results['steps']['validate_students'] = $validStudents;
            $execution->addCompletedStep('validate_students');

            // Step 3: Validate course
            $courseId = $parsed['course_id'];
            $course = $this->validateCourse($courseId, $execution->center_id);
            $results['steps']['validate_course'] = ['course_id' => $course->id, 'validated' => true];
            $execution->addCompletedStep('validate_course');

            // Step 4: Check limits (if any)
            $limitCheck = $this->checkLimits($course, count($validStudents['valid_students']));
            $results['steps']['check_limits'] = $limitCheck;
            $execution->addCompletedStep('check_limits');

            // Step 5: Create enrollments
            $enrollmentResults = $this->createEnrollments(
                $validStudents['valid_students'],
                $course,
                $actor
            );
            $results['steps']['create_enrollments'] = $enrollmentResults;
            $results['enrollments_created'] = $enrollmentResults['created_count'];
            $results['errors'] = array_merge($results['errors'], $enrollmentResults['errors']);
            $execution->addCompletedStep('create_enrollments');

            // Step 6: Send notifications
            $notificationResults = $this->sendNotifications($enrollmentResults['enrollments']);
            $results['steps']['send_notifications'] = $notificationResults;
            $execution->addCompletedStep('send_notifications');

            // Step 7: Create audit logs
            $auditResults = $this->createAuditLogs($execution, $actor, $course, $enrollmentResults['enrollments']);
            $results['steps']['create_audit_logs'] = $auditResults;
            $execution->addCompletedStep('create_audit_logs');

            $execution->markAsCompleted($results);

            return $results;
        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();

            $execution->markAsFailed($results);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{course_id: int, student_ids: array<int, int>}
     */
    private function parseRequest(array $context): array
    {
        $courseId = (int) $context['course_id'];
        /** @var array<int, int> $studentIds */
        $studentIds = array_map('intval', $context['student_ids']);

        return [
            'course_id' => $courseId,
            'student_ids' => $studentIds,
        ];
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return array{valid_students: array<int, User>, invalid_ids: array<int, int>}
     */
    private function validateStudents(array $studentIds, int $centerId): array
    {
        $validStudents = [];
        $invalidIds = [];

        foreach ($studentIds as $studentId) {
            /** @var User|null $student */
            $student = User::find($studentId);

            if ($student === null) {
                $invalidIds[] = $studentId;

                continue;
            }

            // Check if student belongs to center (for branded centers)
            if ($student->center_id !== null && $student->center_id !== $centerId) {
                $invalidIds[] = $studentId;

                continue;
            }

            // Check if user is actually a student
            if (! $student->hasRole('student')) {
                $invalidIds[] = $studentId;

                continue;
            }

            $validStudents[] = $student;
        }

        return [
            'valid_students' => $validStudents,
            'invalid_ids' => $invalidIds,
        ];
    }

    private function validateCourse(int $courseId, int $centerId): Course
    {
        /** @var Course $course */
        $course = Course::findOrFail($courseId);

        if ($course->center_id !== $centerId) {
            throw new \RuntimeException('Course does not belong to the specified center.');
        }

        return $course;
    }

    /**
     * @return array{allowed: bool, limit: int|null, current: int}
     */
    private function checkLimits(Course $course, int $newEnrollmentsCount): array
    {
        // Get current enrollment count
        $currentCount = Enrollment::where('course_id', $course->id)
            ->whereNull('deleted_at')
            ->count();

        // Check if course has a limit
        $limit = $course->enrollment_limit ?? null;

        if ($limit !== null && ($currentCount + $newEnrollmentsCount) > $limit) {
            throw new \RuntimeException(
                "Enrollment limit exceeded. Limit: {$limit}, Current: {$currentCount}, Requested: {$newEnrollmentsCount}"
            );
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'current' => $currentCount,
        ];
    }

    /**
     * @param  array<int, User>  $students
     * @return array{created_count: int, skipped_count: int, enrollments: array<int, Enrollment>, errors: array<int, string>}
     */
    private function createEnrollments(array $students, Course $course, User $actor): array
    {
        $enrollments = [];
        $errors = [];
        $skippedCount = 0;

        foreach ($students as $student) {
            try {
                $enrollment = $this->enrollmentService->enroll(
                    $student,
                    $course,
                    EnrollmentStatus::Active->name,
                    $actor
                );
                $enrollments[] = $enrollment;
            } catch (\Throwable $e) {
                $message = $e->getMessage();

                // Check if already enrolled (not an error, just skip)
                if (str_contains($message, 'already enrolled')) {
                    $skippedCount++;
                } else {
                    $errors[] = "Student {$student->id}: {$message}";
                }
            }
        }

        return [
            'created_count' => count($enrollments),
            'skipped_count' => $skippedCount,
            'enrollments' => $enrollments,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, Enrollment>  $enrollments
     * @return array{sent_count: int}
     */
    private function sendNotifications(array $enrollments): array
    {
        $sentCount = 0;

        foreach ($enrollments as $enrollment) {
            try {
                $this->enrollmentService->sendEnrollmentNotification($enrollment);
                $sentCount++;
            } catch (\Throwable) {
                // Log but don't fail the entire operation
            }
        }

        return [
            'sent_count' => $sentCount,
        ];
    }

    /**
     * @param  array<int, Enrollment>  $enrollments
     * @return array{logged: bool}
     */
    private function createAuditLogs(
        AgentExecution $execution,
        User $actor,
        Course $course,
        array $enrollments
    ): array {
        $this->auditLogService->log(
            $actor,
            $course,
            AuditActions::AGENT_EXECUTED,
            [
                'agent_type' => $this->getType()->value,
                'execution_id' => $execution->id,
                'enrollments_created' => count($enrollments),
                'student_ids' => array_map(fn ($e) => $e->user_id, $enrollments),
            ]
        );

        return [
            'logged' => true,
        ];
    }
}
