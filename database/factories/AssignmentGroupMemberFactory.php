<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssignmentGroup;
use App\Models\AssignmentGroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentGroupMember>
 */
class AssignmentGroupMemberFactory extends Factory
{
    protected $model = AssignmentGroupMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_group_id' => AssignmentGroup::factory(),
            'user_id' => User::factory()->student(),
            'role' => 0, // Member
            'joined_at' => now(),
        ];
    }

    public function leader(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 1,
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 0,
        ]);
    }

    public function forGroup(AssignmentGroup $group): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignment_group_id' => $group->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
