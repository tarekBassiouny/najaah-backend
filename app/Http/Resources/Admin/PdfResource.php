<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pdf
 */
class PdfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pdf $pdf */
        $pdf = $this->resource;

        return [
            'id' => $pdf->id,
            'center_id' => $pdf->center_id,
            'title' => $pdf->translate('title'),
            'description' => $pdf->translate('description'),
            'source_type' => $pdf->source_type,
            'source_provider' => $pdf->source_provider,
            'source_id' => $pdf->source_id,
            'file_extension' => $pdf->file_extension,
            'file_size_kb' => $pdf->file_size_kb,
            'created_by' => $pdf->created_by,
            'created_at' => $pdf->created_at,
        ];
    }
}
