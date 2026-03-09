<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Assignment;

use App\Models\AssignmentSubmissionFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssignmentSubmissionFile
 */
class AssignmentSubmissionFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_size_kb' => $this->file_size_kb,
            'file_size_mb' => $this->getFileSizeMb(),
            'file_type' => $this->file_type,
            'file_extension' => $this->getFileExtension(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
