<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentSubmission>
 */
class AssignmentSubmissionFactory extends Factory
{
    protected $model = AssignmentSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $center = Center::factory();
        $assignment = Assignment::factory()->state(['center_id' => $center]);

        return [
            'assignment_id' => $assignment,
            'user_id' => User::factory()->student()->for($center, 'center'),
            'enrollment_id' => Enrollment::factory(),
            'center_id' => $center,
            'group_id' => null,
            'submission_type' => SubmissionType::Text,
            'text_content' => fake()->paragraphs(3, true),
            'link_url' => null,
            'status' => SubmissionStatus::Draft,
            'submitted_at' => null,
            'is_late' => false,
            'days_late' => 0,
            'score' => null,
            'score_after_penalty' => null,
            'passed' => false,
            'feedback' => null,
            'graded_by' => null,
            'graded_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Draft,
            'submitted_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function graded(float $score = 85.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Graded,
            'submitted_at' => now()->subDays(2),
            'score' => $score,
            'score_after_penalty' => $score,
            'passed' => $score >= 60,
            'feedback' => fake()->paragraph(),
            'graded_by' => User::factory(),
            'graded_at' => now(),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Returned,
            'submitted_at' => now()->subDays(2),
            'feedback' => 'Please revise and resubmit.',
            'graded_by' => User::factory(),
            'graded_at' => now(),
        ]);
    }

    public function late(int $daysLate = 2): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_late' => true,
            'days_late' => $daysLate,
        ]);
    }

    public function passed(): static
    {
        $score = fake()->randomFloat(2, 60, 100);

        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Graded,
            'submitted_at' => now()->subDays(2),
            'score' => $score,
            'score_after_penalty' => $score,
            'passed' => true,
            'graded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        $score = fake()->randomFloat(2, 0, 59);

        return $this->state(fn (array $attributes): array => [
            'status' => SubmissionStatus::Graded,
            'submitted_at' => now()->subDays(2),
            'score' => $score,
            'score_after_penalty' => $score,
            'passed' => false,
            'graded_at' => now(),
        ]);
    }

    public function fileSubmission(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_type' => SubmissionType::File,
            'text_content' => null,
            'link_url' => null,
        ]);
    }

    public function textSubmission(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_type' => SubmissionType::Text,
            'text_content' => fake()->paragraphs(3, true),
            'link_url' => null,
        ]);
    }

    public function linkSubmission(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_type' => SubmissionType::Link,
            'text_content' => null,
            'link_url' => fake()->url(),
        ]);
    }

    public function forAssignment(Assignment $assignment): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignment_id' => $assignment->id,
            'center_id' => $assignment->center_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
