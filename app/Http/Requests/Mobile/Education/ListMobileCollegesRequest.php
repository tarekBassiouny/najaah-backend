<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Education;

use Illuminate\Foundation\Http\FormRequest;

class ListMobileCollegesRequest extends FormRequest
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
                'example' => 'Cairo University',
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
