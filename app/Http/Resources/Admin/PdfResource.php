<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
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
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'title' => $pdf->translate('title'),
            'description' => $pdf->translate('description'),
            'source_type' => $pdf->source_type,
            'source_provider' => $pdf->source_provider,
            'source_id' => $pdf->source_id,
            'source_label' => $pdf->source_type,
            'file_extension' => $pdf->file_extension,
            'file_size_kb' => $pdf->file_size_kb,
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $pdf->created_at,
        ];
    }
}
