<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class BulkAttachPdfsToSectionRequest extends FormRequest
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
            'pdf_ids' => ['required', 'array', 'min:1', 'max:50'],
            'pdf_ids.*' => ['required', 'integer', 'exists:pdfs,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'pdf_ids' => [
                'description' => 'Array of PDF IDs to attach to the section.',
                'example' => [1, 2, 3],
            ],
        ];
    }
}
