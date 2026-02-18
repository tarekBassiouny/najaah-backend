<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Survey
 */
class SurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Survey $survey */
        $survey = $this->resource;

        return [
            'id' => $survey->id,
            'scope_type' => $survey->scope_type->value,
            'scope_type_label' => $survey->scope_type->label(),
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'title' => $survey->translate('title'),
            'description' => $survey->translate('description'),
            'title_translations' => $survey->title_translations,
            'description_translations' => $survey->description_translations,
            'type' => $survey->type->value,
            'type_label' => $survey->type->label(),
            'is_active' => $survey->is_active,
            'is_mandatory' => $survey->is_mandatory,
            'allow_multiple_submissions' => $survey->allow_multiple_submissions,
            'start_at' => $survey->start_at?->toDateString(),
            'end_at' => $survey->end_at?->toDateString(),
            'is_available' => $survey->isAvailable(),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'questions' => SurveyQuestionResource::collection($this->whenLoaded('questions')),
            'assignments' => SurveyAssignmentResource::collection($this->whenLoaded('assignments')),
            'responses_count' => $this->when($survey->responses_count !== null, $survey->responses_count),
            'submitted_users_count' => $this->when(
                $survey->submitted_users_count !== null || $survey->responses_count !== null,
                (int) ($survey->submitted_users_count ?? $survey->responses_count ?? 0)
            ),
            'created_at' => $survey->created_at->toIso8601String(),
            'updated_at' => $survey->updated_at->toIso8601String(),
        ];
    }
}
