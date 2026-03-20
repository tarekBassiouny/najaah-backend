<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LearningAsset;

use App\Enums\LearningAssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningAssetStatusRequest extends FormRequest
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
            'status' => ['required', 'integer', Rule::enum(LearningAssetStatus::class)],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'New learning asset status enum value.',
                'example' => LearningAssetStatus::Published->value,
            ],
        ];
    }
}
