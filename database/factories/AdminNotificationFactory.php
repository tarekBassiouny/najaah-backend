<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminNotification>
 */
class AdminNotificationFactory extends Factory
{
    protected $model = AdminNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'center_id' => null,
            'type' => $this->faker->randomElement(AdminNotificationType::cases()),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'data' => [
                'entity_type' => 'test',
                'entity_id' => $this->faker->randomNumber(),
            ],
            'read_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    public function forCenter(Center $center): static
    {
        return $this->state(fn (): array => [
            'center_id' => $center->id,
        ]);
    }

    public function ofType(AdminNotificationType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (): array => [
            'read_at' => null,
        ]);
    }

    public function systemAlert(): static
    {
        return $this->ofType(AdminNotificationType::SYSTEM_ALERT);
    }

    public function deviceChangeRequest(): static
    {
        return $this->ofType(AdminNotificationType::DEVICE_CHANGE_REQUEST);
    }

    public function extraViewRequest(): static
    {
        return $this->ofType(AdminNotificationType::EXTRA_VIEW_REQUEST);
    }

    public function surveyResponse(): static
    {
        return $this->ofType(AdminNotificationType::SURVEY_RESPONSE);
    }

    public function newEnrollment(): static
    {
        return $this->ofType(AdminNotificationType::NEW_ENROLLMENT);
    }

    public function centerOnboarding(): static
    {
        return $this->ofType(AdminNotificationType::CENTER_ONBOARDING);
    }
}
