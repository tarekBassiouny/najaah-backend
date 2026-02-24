<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\SurveyAssignableType;
use App\Models\Center;
use App\Models\Course;
use App\Models\SurveyAssignment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyAssignment
 */
class SurveyAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SurveyAssignment $assignment */
        $assignment = $this->resource;

        $assignableData = $this->getAssignableData($assignment);

        return [
            'id' => $assignment->id,
            'type' => $assignment->assignable_type->value,
            'type_label' => $assignment->assignable_type->label(),
            'assignable_id' => $assignment->assignable_id,
            'assignable_name' => $assignableData['name'],
            'created_at' => $assignment->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array{name: string|null}
     */
    private function getAssignableData(SurveyAssignment $assignment): array
    {
        $model = $assignment->assignable_model;

        if ($model === null) {
            return ['name' => null];
        }

        $name = match ($assignment->assignable_type) {
            SurveyAssignableType::Center => $model instanceof Center ? $model->translate('name') : null,
            SurveyAssignableType::Course => $model instanceof Course ? $model->translate('title') : null,
            SurveyAssignableType::Video => $model instanceof Video ? $model->translate('title') : null,
            SurveyAssignableType::User, SurveyAssignableType::All => $model instanceof User ? ($model->name ?? $model->email) : null,
        };

        return ['name' => $name];
    }
}
