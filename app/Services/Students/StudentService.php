<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Access\StudentAccessService;
use App\Services\Centers\CenterScopeService;
use App\Services\Students\Contracts\StudentNotificationServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Str;

class StudentService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentNotificationServiceInterface $notificationService,
        private readonly StudentAccessService $studentAccessService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
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
            $centerId = (int) $data['center_id'];
            $user->centers()->syncWithoutDetaching([
                $centerId => ['type' => 'student'],
            ]);
        }

        $user = $user->refresh() ?? $user;

        // Send welcome message with app download links (non-blocking)
        $this->notificationService->sendWelcomeMessage($user);

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
        $this->studentAccessService->assertStudent(
            $user,
            'User is not a student.',
            ErrorCodes::NOT_STUDENT,
            422
        );

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? null,
        ], static fn ($value): bool => $value !== null);

        $user->update($payload);

        return $user->refresh() ?? $user;
    }

    public function delete(User $user, ?User $actor = null): void
    {
        $this->studentAccessService->assertStudent(
            $user,
            'User is not a student.',
            ErrorCodes::NOT_STUDENT,
            422
        );

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $user);
        }

        $user->delete();
    }
}
