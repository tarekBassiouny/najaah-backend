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
}
