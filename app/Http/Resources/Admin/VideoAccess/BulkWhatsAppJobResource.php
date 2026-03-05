<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\BulkWhatsAppJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin BulkWhatsAppJob
 */
class BulkWhatsAppJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BulkWhatsAppJob $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'status' => $resource->status->value,
            'status_key' => Str::snake($resource->status->name),
            'status_label' => $resource->status->name,
            'format' => $resource->format->value,
            'total_codes' => $resource->total_codes,
            'sent_count' => $resource->sent_count,
            'failed_count' => $resource->failed_count,
            'pending_count' => max(0, $resource->total_codes - ($resource->sent_count + $resource->failed_count)),
            'progress_percent' => $resource->progressPercent(),
            'settings' => $resource->settings,
            'started_at' => $resource->started_at,
            'completed_at' => $resource->completed_at,
            'created_by' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }
}
