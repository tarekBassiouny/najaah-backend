<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetCatalogRequest extends FormRequest
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
            'section_id' => ['sometimes', 'integer', 'min:1'],
            'source_type' => ['sometimes', 'string', Rule::in(['video', 'pdf'])],
            'source_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'section_id' => [
                'description' => 'Limit the asset catalog to sources inside one section.',
                'example' => '8',
            ],
            'source_type' => [
                'description' => 'Limit the catalog to one source type.',
                'example' => 'video',
            ],
            'source_id' => [
                'description' => 'Limit the catalog to one specific source entity.',
                'example' => '34',
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
