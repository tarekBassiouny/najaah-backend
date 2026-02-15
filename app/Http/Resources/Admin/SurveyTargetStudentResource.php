<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\UserStatus;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin User
 */
class SurveyTargetStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $student */
        $student = $this->resource;
        $status = $student->status instanceof UserStatus
            ? $student->status
            : ($student->status !== null ? UserStatus::tryFrom((int) $student->status) : null);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'username' => $student->username,
            'email' => $student->email,
            'phone' => $student->phone,
            'center_id' => $student->center_id,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'status' => $status?->value ?? $student->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
        ];
    }
}
