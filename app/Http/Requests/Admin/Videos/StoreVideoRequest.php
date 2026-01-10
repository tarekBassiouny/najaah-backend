<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['sometimes', 'nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
            'title_translations' => ['prohibited'],
            'description_translations' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'Video title in the request locale.',
                'example' => 'Introduction',
            ],
            'description' => [
                'description' => 'Optional description in the request locale.',
                'example' => 'Overview of the lesson.',
            ],
            'tags' => [
                'description' => 'Optional tags array.',
                'example' => ['module' => 'intro'],
            ],
        ];
    }
}
