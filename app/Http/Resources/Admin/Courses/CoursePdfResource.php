<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Http\Resources\Admin\Summary\PdfSummaryResource;
use App\Models\Pivots\CoursePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CoursePdf
 */
class CoursePdfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CoursePdf $pivot */
        $pivot = $this->resource;

        return [
            'id' => $pivot->id,
            'pdf' => new PdfSummaryResource($this->whenLoaded('pdf')),
            'order_index' => $pivot->order_index,
            'visible' => $pivot->visible,
        ];
    }
}
