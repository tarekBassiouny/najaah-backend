<?php

declare(strict_types=1);

namespace App\Http\Resources\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'quizzes' => $data['quizzes'] ?? [],
            'assignments' => $data['assignments'] ?? [],
            'learning_assets' => $data['learning_assets'] ?? [],
            'overall_completion_percentage' => $data['overall_completion_percentage'] ?? 0,
            'overall_content_completion_percentage' => $data['overall_content_completion_percentage'] ?? 0,
            'all_required_passed' => $data['all_required_passed'] ?? false,
        ];
    }
}
