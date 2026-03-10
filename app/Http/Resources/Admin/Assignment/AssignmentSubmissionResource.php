<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Assignment;

use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssignmentSubmission
 */
class AssignmentSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'group_id' => $this->group_id,
            'submission_type' => $this->submission_type->value,
            'submission_type_label' => $this->submission_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'is_late' => $this->is_late,
            'days_late' => $this->days_late,
            'score' => $this->score !== null ? (float) $this->score : null,
            'score_after_penalty' => $this->score_after_penalty !== null ? (float) $this->score_after_penalty : null,
            'passed' => $this->passed,
            'files_count' => $this->whenCounted('files', $this->files_count ?? $this->files()->count()),
            'graded_at' => $this->graded_at?->toIso8601String(),
            'grader' => $this->whenLoaded('grader', fn (): ?array => $this->grader ? [
                'id' => $this->grader->id,
                'name' => $this->grader->name,
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
