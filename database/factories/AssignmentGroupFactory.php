<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentGroup>
 */
class AssignmentGroupFactory extends Factory
{
    protected $model = AssignmentGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory()->groupAssignment(),
            'name' => 'Team '.fake()->word(),
            'created_by' => User::factory()->student(),
        ];
    }

    public function forAssignment(Assignment $assignment): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignment_id' => $assignment->id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }
}
