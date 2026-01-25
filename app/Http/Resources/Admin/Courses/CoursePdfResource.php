<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Http\Resources\Admin\PdfResource;
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
            'pdf_id' => $pivot->pdf_id,
            'order_index' => $pivot->order_index,
            'visible' => $pivot->visible,
            'pdf' => new PdfResource($this->whenLoaded('pdf')),
        ];
    }
}
