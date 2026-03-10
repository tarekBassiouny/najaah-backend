<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Assignment;

use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssignmentSubmission
 */
class AssignmentSubmissionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'assignment' => $this->whenLoaded('assignment', fn (): array => [
                'id' => $this->assignment->id,
                'title' => $this->assignment->translate('title'),
                'max_points' => (float) $this->assignment->max_points,
                'passing_score' => (float) $this->assignment->passing_score,
            ]),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('group', fn (): ?array => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'members' => $this->group->members->map(fn ($member): array => [
                    'user_id' => $member->user_id,
                    'name' => $member->user->name,
                    'role' => $member->getRoleLabel(),
                ]),
            ] : null),
            'submission_type' => $this->submission_type->value,
            'submission_type_label' => $this->submission_type->label(),
            'text_content' => $this->text_content,
            'link_url' => $this->link_url,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'is_late' => $this->is_late,
            'days_late' => $this->days_late,
            'score' => $this->score !== null ? (float) $this->score : null,
            'score_after_penalty' => $this->score_after_penalty !== null ? (float) $this->score_after_penalty : null,
            'passed' => $this->passed,
            'feedback' => $this->feedback,
            'files' => AssignmentSubmissionFileResource::collection($this->whenLoaded('files')),
            'graded_by' => $this->graded_by,
            'grader' => $this->whenLoaded('grader', fn (): ?array => $this->grader ? [
                'id' => $this->grader->id,
                'name' => $this->grader->name,
            ] : null),
            'graded_at' => $this->graded_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
