<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Sections;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pdf
 */
class SectionPdfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pdf $pdf */
        $pdf = $this->resource;
        /** @var CoursePdf|null $pivot */
        $pivot = $pdf->pivot instanceof CoursePdf ? $pdf->pivot : null;

        return [
            'id' => $pivot?->id ?? $pdf->id,
            'pdf_id' => $pdf->id,
            'title' => $pdf->translate('title'),
            'file_path' => $pdf->source_url,
            'size' => $pdf->file_size_kb,
            'order' => $pivot?->order_index,
            'created_at' => $pivot?->created_at ?? $pdf->created_at,
            'updated_at' => $pivot?->updated_at ?? $pdf->updated_at,
        ];
    }
}
