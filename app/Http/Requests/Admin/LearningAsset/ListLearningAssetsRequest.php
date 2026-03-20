<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LearningAsset;

use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLearningAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'attachable_type' => ['sometimes', 'string', Rule::in(['video', 'pdf'])],
            'attachable_id' => ['sometimes', 'integer', 'min:1'],
            'asset_type' => ['sometimes', 'string', Rule::enum(LearningAssetType::class)],
            'status' => ['sometimes', 'integer', Rule::enum(LearningAssetStatus::class)],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'attachable_type' => [
                'description' => 'Filter learning assets by source type.',
                'example' => 'video',
            ],
            'attachable_id' => [
                'description' => 'Filter learning assets by source entity ID.',
                'example' => '34',
            ],
            'asset_type' => [
                'description' => 'Filter by asset type.',
                'example' => LearningAssetType::Summary->value,
            ],
            'status' => [
                'description' => 'Filter by asset status enum value.',
                'example' => (string) LearningAssetStatus::Published->value,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
