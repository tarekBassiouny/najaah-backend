<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LearningAsset;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLearningAssetRequest extends FormRequest
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
            'title_translations' => ['sometimes', 'array'],
            'title_translations.en' => ['sometimes', 'string', 'max:255'],
            'title_translations.ar' => ['sometimes', 'string', 'max:255'],
            'content_translations' => ['sometimes', 'nullable', 'array'],
            'content_translations.en' => ['sometimes', 'string'],
            'content_translations.ar' => ['sometimes', 'string'],
            'payload' => ['sometimes', 'array'],
        ];
    }
}
