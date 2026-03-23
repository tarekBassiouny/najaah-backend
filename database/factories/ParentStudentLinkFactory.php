<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Models\Center;
use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentStudentLinkFactory extends Factory
{
    protected $model = ParentStudentLink::class;

    public function definition(): array
    {
        return [
            'parent_user_id' => User::factory()->state(['is_parent' => true]),
            'student_user_id' => User::factory()->student(),
            'center_id' => Center::factory(),
            'status' => ParentLinkStatus::Active,
            'linked_by' => null,
            'link_method' => ParentLinkMethod::AdminManaged,
            'linked_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ParentLinkStatus::PendingApproval,
        ]);
    }

    public function autoMatched(): static
    {
        return $this->state(fn (): array => [
            'link_method' => ParentLinkMethod::AutoMatched,
        ]);
    }

    public function parentRequested(): static
    {
        return $this->state(fn (): array => [
            'status' => ParentLinkStatus::PendingApproval,
            'link_method' => ParentLinkMethod::ParentRequested,
        ]);
    }
}
