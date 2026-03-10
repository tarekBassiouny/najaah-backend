<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $center = Center::factory();
        $course = Course::factory()->for($center, 'center');

        return [
            'center_id' => $center,
            'course_id' => $course,
            'title_translations' => [
                'en' => fake()->sentence(3),
                'ar' => 'واجب '.fake()->word(),
            ],
            'description_translations' => [
                'en' => fake()->paragraph(),
                'ar' => 'وصف الواجب',
            ],
            'attachable_type' => 'course',
            'attachable_id' => null,
            'submission_types' => ['file', 'text'],
            'allowed_file_types' => ['pdf', 'doc', 'docx'],
            'max_file_size_mb' => 10,
            'max_files' => 3,
            'is_group_assignment' => false,
            'max_group_size' => null,
            'max_points' => 100.00,
            'passing_score' => 60.00,
            'is_required' => fake()->boolean(30),
            'is_active' => true,
            'due_date' => now()->addDays(14),
            'late_submission_allowed' => true,
            'late_penalty_percent' => 10.00,
            'available_from' => null,
            'available_until' => null,
            'order_index' => fake()->numberBetween(0, 10),
            'created_by' => User::factory()->for($center, 'center'),
        ];
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes): array => [
            'center_id' => $course->center_id,
            'course_id' => $course->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => true,
        ]);
    }

    public function groupAssignment(int $maxSize = 5): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_group_assignment' => true,
            'max_group_size' => $maxSize,
        ]);
    }

    public function fileOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_types' => ['file'],
        ]);
    }

    public function textOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_types' => ['text'],
        ]);
    }

    public function linkOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'submission_types' => ['link'],
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now()->subDays(7),
        ]);
    }

    public function noLateSubmission(): static
    {
        return $this->state(fn (array $attributes): array => [
            'late_submission_allowed' => false,
        ]);
    }

    public function withLatePenalty(float $percent = 10.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'late_submission_allowed' => true,
            'late_penalty_percent' => $percent,
        ]);
    }
}
