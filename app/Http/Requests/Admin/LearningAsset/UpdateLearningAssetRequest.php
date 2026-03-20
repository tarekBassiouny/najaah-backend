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

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Localized asset titles keyed by locale.',
                'example' => [
                    'en' => 'Chapter Summary',
                    'ar' => 'ملخص الفصل',
                ],
            ],
            'content_translations' => [
                'description' => 'Localized asset body content keyed by locale.',
                'example' => [
                    'en' => 'This lesson focuses on the core ideas...',
                    'ar' => 'يركز هذا الدرس على الأفكار الأساسية...',
                ],
            ],
            'payload' => [
                'description' => 'Optional structured payload for asset-specific content such as cards, quiz metadata, or activity schema.',
                'example' => [
                    'cards' => [
                        ['front' => 'Term', 'back' => 'Definition'],
                    ],
                ],
            ],
        ];
    }
}
