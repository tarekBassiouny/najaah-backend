<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\CenterType;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\Pivots\UserCenter;
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

        if (isset($data['center_id']) && is_numeric($data['center_id'])) {
            $this->attachStudentToCenter($user, (int) $data['center_id']);
        }

        $user = $user->refresh() ?? $user;

        // Send welcome message with app download links (non-blocking)
        $this->notificationService->sendWelcomeMessage($user);
        $this->auditLogService->log($actor, $user, AuditActions::STUDENT_CREATED);

        return $user;
    }

    /**
     * Create or attach a student for center-scoped admin flows.
     *
     * Branded center:
     * - Create student with center_id = center.id
     *
     * Unbranded center:
     * - If system student exists (center_id = null), attach center relation only
     * - Else create system student (center_id = null), then attach relation
     *
     * @param  array<string, mixed>  $data
     */
    public function createForCenter(Center $center, array $data, ?User $actor = null): User
    {
        if ($center->type === CenterType::Branded) {
            $data['center_id'] = (int) $center->id;

            return $this->create($data, $actor);
        }

        $existingSystemStudent = $this->findExistingSystemStudent($data);

        if ($existingSystemStudent instanceof User) {
            $this->attachStudentToCenter($existingSystemStudent, (int) $center->id);

            return $existingSystemStudent->refresh() ?? $existingSystemStudent;
        }

        $data['center_id'] = null;
        $created = $this->create($data, $actor);
        $this->attachStudentToCenter($created, (int) $center->id);

        return $created->refresh() ?? $created;
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

    /**
     * @param  array<int, int|string>  $studentIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{student_id: int|string, reason: string}>
     * }
     */
    public function bulkUpdateStatusForCenter(User $admin, Center $center, int $status, array $studentIds): array
    {
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        $uniqueIds = array_values(array_unique(array_map('intval', $studentIds)));
        $students = User::query()
            ->where('is_student', true)
            ->whereIn('id', $uniqueIds)
            ->whereHas('centers', function ($query) use ($center): void {
                $query->where('centers.id', (int) $center->id)
                    ->where('user_centers.type', 'student');
            })
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

            if ((int) $student->status === $status) {
                $results['skipped'][] = $studentId;

                continue;
            }

            $student->update(['status' => $status]);

            $statusEnum = UserStatus::tryFrom($status);
            $this->auditLogService->log($admin, $student, AuditActions::STUDENT_UPDATED, [
                'status' => $status,
                'status_label' => $statusEnum?->name,
            ]);

            $results['updated'][] = $student->refresh() ?? $student;
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

    public function deleteFromCenter(User $student, Center $center, ?User $actor = null): void
    {
        if (! $student->is_student) {
            throw new \App\Exceptions\DomainException(
                'User is not a student.',
                ErrorCodes::NOT_STUDENT,
                422
            );
        }

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminCenterId($actor, (int) $center->id);
        }

        $association = UserCenter::query()
            ->where('user_id', (int) $student->id)
            ->where('center_id', (int) $center->id)
            ->where('type', 'student')
            ->first();

        if (! $association instanceof UserCenter) {
            throw new \App\Exceptions\NotFoundException('Student not found.', 404);
        }

        if (is_numeric($student->center_id) && (int) $student->center_id === (int) $center->id) {
            $this->auditLogService->log($actor, $student, AuditActions::STUDENT_DELETED);
            $student->delete();

            return;
        }

        $association->delete();

        $this->auditLogService->log($actor, $student, AuditActions::STUDENT_UPDATED, [
            'detached_center_id' => (int) $center->id,
        ]);
    }

    private function attachStudentToCenter(User $student, int $centerId): void
    {
        $center = Center::query()->find($centerId);

        if (! $center instanceof Center) {
            return;
        }

        $existing = UserCenter::withTrashed()
            ->where('user_id', (int) $student->id)
            ->where('center_id', $centerId)
            ->where('type', 'student')
            ->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return;
        }

        UserCenter::create([
            'user_id' => (int) $student->id,
            'center_id' => $centerId,
            'type' => 'student',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findExistingSystemStudent(array $data): ?User
    {
        $phone = (string) ($data['phone'] ?? '');
        $countryCode = (string) ($data['country_code'] ?? '');

        if ($phone === '' || $countryCode === '') {
            return null;
        }

        return User::query()
            ->where('is_student', true)
            ->whereNull('center_id')
            ->where('phone', $phone)
            ->where('country_code', $countryCode)
            ->first();
    }
}
