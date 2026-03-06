<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\StudentSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\VideoAccessCode;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin VideoAccessCode
 */
class VideoAccessCodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoAccessCode $resource */
        $resource = $this->resource;

        /** @var VideoApprovalCodeServiceInterface $codeService */
        $codeService = app(VideoApprovalCodeServiceInterface::class);

        return [
            'id' => $resource->id,
            'code' => $resource->code,
            'status' => $resource->status->value,
            'status_key' => Str::snake($resource->status->name),
            'status_label' => $resource->status->name,
            'qr_code_url' => $codeService->getQrCodeDataUrl($resource),
            'expires_at' => $resource->expires_at,
            'used_at' => $resource->used_at,
            'student' => new StudentSummaryResource($this->whenLoaded('user')),
            'video' => new VideoSummaryResource($this->whenLoaded('video')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'request' => new VideoAccessRequestResource($this->whenLoaded('request')),
            'generated_by' => new UserSummaryResource($this->whenLoaded('generator')),
            'generated_at' => $resource->generated_at,
            'revoked_at' => $resource->revoked_at,
            'revoked_by' => new UserSummaryResource($this->whenLoaded('revoker')),
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }
}
