<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\User;
use App\Services\Access\StudentAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Students\Contracts\StudentNotificationServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Str;

class StudentService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentNotificationServiceInterface $notificationService,
        private readonly StudentAccessService $studentAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $user = User::create([
            'name' => (string) $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => (string) $data['phone'],
            'country_code' => (string) $data['country_code'],
            'center_id' => $data['center_id'] ?? null,
            'password' => Str::random(32),
            'is_student' => true,
            'status' => UserStatus::Active,
        ]);

        // When admin creates a student for any center (branded or unbranded),
        // associate them immediately so they appear in the center's student list.
        // Note: For self-registered students (mobile app), center association is
        // created via EnrollmentObserver when they enroll in a course.
        if (isset($data['center_id']) && is_numeric($data['center_id'])) {
            $centerId = (int) $data['center_id'];
            $center = Center::find($centerId);

            if ($center !== null) {
                $user->centers()->syncWithoutDetaching([
                    $centerId => ['type' => 'student'],
                ]);
            }
        }

        $user = $user->refresh() ?? $user;

        // Send welcome message with app download links (non-blocking)
        $this->notificationService->sendWelcomeMessage($user);
        $this->auditLogService->log($actor, $user, AuditActions::STUDENT_CREATED);

        return $user;
    }

    /**
     * Manually send welcome message to a student.
     */
    public function sendWelcomeMessage(User $user): bool
    {
        $this->studentAccessService->assertStudent(
            $user,
            'User is not a student.',
            ErrorCodes::NOT_STUDENT,
            422
        );

        return $this->notificationService->sendWelcomeMessage($user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        if (! $user->is_student) {
            throw new \App\Exceptions\DomainException(
                'User is not a student.',
                ErrorCodes::NOT_STUDENT,
                422
            );
        }

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? null,
        ], static fn ($value): bool => $value !== null);

        $user->update($payload);
        $metadata = $payload;
        if (array_key_exists('status', $metadata)) {
            $status = UserStatus::tryFrom((int) $metadata['status']);
            $metadata['status_label'] = $status?->name;
        }

        $this->auditLogService->log($actor, $user, AuditActions::STUDENT_UPDATED, $metadata);

        return $user->refresh() ?? $user;
    }

    /**
     * @param  array<int, int|string>  $studentIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{student_id: int|string, reason: string}>
     * }
     */
    public function bulkUpdateStatus(User $admin, int $status, array $studentIds): array
    {
        $uniqueIds = array_values(array_unique(array_map('intval', $studentIds)));
        $students = User::query()
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        $results = [
            'updated' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $studentId) {
            $student = $students->get($studentId);

            if (! $student instanceof User) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found.',
                ];

                continue;
            }

            try {
                if ((int) $student->status === $status) {
                    $results['skipped'][] = $studentId;

                    continue;
                }

                $updated = $this->update($student, ['status' => $status], $admin);
                $results['updated'][] = $updated;
            } catch (\App\Exceptions\DomainException $exception) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function delete(User $user, ?User $actor = null): void
    {
        if (! $user->is_student) {
            throw new \App\Exceptions\DomainException(
                'User is not a student.',
                ErrorCodes::NOT_STUDENT,
                422
            );
        }

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $this->auditLogService->log($actor, $user, AuditActions::STUDENT_DELETED);
        $user->delete();
    }
}
