<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student representation for enrollment responses.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin User
 */
class StudentSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $student */
        $student = $this->resource;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
        ];
    }
}
