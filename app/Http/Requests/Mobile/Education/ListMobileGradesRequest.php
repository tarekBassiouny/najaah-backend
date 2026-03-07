<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Education;

use Illuminate\Foundation\Http\FormRequest;

class ListMobileGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string'],
            'stage' => ['sometimes', 'integer', 'in:0,1,2,3,4'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'search' => [
                'description' => 'Optional localized name search term.',
                'example' => 'Grade 9',
            ],
            'stage' => [
                'description' => 'Optional educational stage filter (0..4).',
                'example' => '2',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
