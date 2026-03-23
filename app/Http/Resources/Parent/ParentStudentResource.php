<?php

declare(strict_types=1);

namespace App\Http\Resources\Parent;

use App\Models\ParentStudentLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParentStudentLink
 */
class ParentStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ParentStudentLink $link */
        $link = $this->resource;
        $student = $link->student;

        return [
            'link_id' => $link->id,
            'student_id' => $student->id,
            'name' => $student->name,
            'phone' => $student->phone,
            'status' => $link->status->name,
            'link_method' => $link->link_method->name,
            'linked_at' => $link->linked_at->toISOString(),
        ];
    }
}
