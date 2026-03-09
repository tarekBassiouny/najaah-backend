<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Assignment;

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
            'graded_at' => $this->graded_at?->toIso8601String(),
            'files' => $this->whenLoaded('files', fn () => $this->files->map(fn ($file) => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_size_kb' => $file->file_size_kb,
                'file_type' => $file->file_type,
            ])),
            'group' => $this->whenLoaded('group', fn () => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'members_count' => $this->group->members->count(),
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
